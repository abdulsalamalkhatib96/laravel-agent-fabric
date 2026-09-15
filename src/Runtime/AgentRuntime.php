<?php

namespace Evolvex\AgentFabric\Runtime;

use Evolvex\AgentFabric\Agents\AgentBlueprint;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\BudgetManager;
use Evolvex\AgentFabric\Contracts\MemoryStore;
use Evolvex\AgentFabric\Contracts\ModelGateway;
use Evolvex\AgentFabric\Contracts\ModelRouter;
use Evolvex\AgentFabric\Contracts\Retriever;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Contracts\UsageMeter;
use Evolvex\AgentFabric\Contracts\Verifier;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AgentResult;
use Evolvex\AgentFabric\Data\ModelRequest;
use Evolvex\AgentFabric\Enums\MemoryKind;
use Evolvex\AgentFabric\Enums\RunStatus;
use Evolvex\AgentFabric\Enums\StepType;
use Evolvex\AgentFabric\Exceptions\ApprovalRequiredException;
use Evolvex\AgentFabric\Policies\PolicyEngine;
use Evolvex\AgentFabric\Runtime\Protocol\EnvelopeParser;
use Evolvex\AgentFabric\Tools\ToolExecutor;
use Evolvex\AgentFabric\Tools\ToolRegistry;
use Throwable;

final class AgentRuntime
{
    public function __construct(
        private readonly ModelRouter $router, private readonly ModelGateway $gateway, private readonly Retriever $retriever,
        private readonly MemoryStore $memory, private readonly RunRepository $runs, private readonly UsageMeter $usage,
        private readonly BudgetManager $budget, private readonly Verifier $verifier, private readonly ToolExecutor $tools,
        private readonly PromptBuilder $prompts, private readonly EnvelopeParser $parser, private readonly PolicyEngine $policies,
        private readonly ApprovalManager $approvals,
    ) {}

    public function run(AgentBlueprint $blueprint, AgentContext $context, string $input): AgentResult
    {
        $definition=$blueprint->definition(); $runId=$this->runs->create($definition,$context,$input);
        return $this->execute($runId,$definition,$context,$input,[],null);
    }

    public function resume(AgentBlueprint $blueprint, AgentContext $context, string $runId, string $approvalId): AgentResult
    {
        $run=$this->runs->get($runId); if(!$run) throw new \InvalidArgumentException("Unknown run [{$runId}].");
        if((string)$run['tenant_id']!==(string)$context->tenantId) throw new \RuntimeException('Cross-tenant run resume denied.');
        $approval=$this->approvals->find($approvalId);
        if(!$approval || !in_array(($approval['status']??null),['approved','rejected'],true)) throw new \RuntimeException('Approval decision is still pending or missing.');
        if((string)($approval['run_id']??'')!==$runId || (string)($approval['tenant_id']??'')!==(string)$context->tenantId) throw new \RuntimeException('Approval does not belong to this run or tenant.');
        $transcript=$this->transcript($runId);
        $approvedCall=null;
        if(($approval['status']??null)==='approved'){
            $approvedCall=['approval_id'=>$approvalId,'tool'=>(string)$approval['tool_name'],'arguments'=>$approval['arguments']??[]];
        }else{
            $transcript[]=['role'=>'tool','tool'=>(string)$approval['tool_name'],'success'=>false,'message'=>'Human approval rejected: '.($approval['decision_reason']??'not approved'),'evidence'=>[]];
            $this->runs->addStep($runId,StepType::Approval,['approval_id'=>$approvalId],['approved'=>false,'reason'=>$approval['decision_reason']??null]);
        }
        return $this->execute($runId,$blueprint->definition(),$context,(string)$run['input'],$transcript,$approvedCall);
    }

    public function continueWithUser(AgentBlueprint $blueprint, AgentContext $context, string $runId, string $message): AgentResult
    {
        $run=$this->runs->get($runId); if(!$run) throw new \InvalidArgumentException("Unknown run [{$runId}].");
        if((string)$run['tenant_id']!==(string)$context->tenantId) throw new \RuntimeException('Cross-tenant run continuation denied.');
        if(($run['status']??null)!==RunStatus::WaitingForUser->value) throw new \RuntimeException('Run is not waiting for user input.');
        $transcript=$this->transcript($runId);$transcript[]=['role'=>'user','content'=>$message];
        $this->runs->addStep($runId,StepType::Prompt,['follow_up'=>$message],[]);
        $combined=(string)$run['input']."\n\nUSER FOLLOW-UP:\n".$message;
        return $this->execute($runId,$blueprint->definition(),$context,$combined,$transcript,null);
    }

    private function execute(string $runId, $definition, AgentContext $context, string $input, array $transcript, ?array $approvedCall): AgentResult
    {
        try {
            $this->policies->enforce($definition->policies,$context,'start',['input'=>$input]);
            $this->runs->transition($runId,RunStatus::Running,['started_at'=>now()]);
            $registry=$this->resolveTools($definition->tools);
            $knowledge=$this->retriever->retrieve($input,$context,$definition->knowledgeSources,(int)config('agent-fabric.knowledge.default_limit',8));
            $this->runs->addStep($runId,StepType::Retrieve,['query'=>$input],['count'=>count($knowledge),'results'=>array_map(fn($r)=>['document_id'=>$r->documentId,'chunk_id'=>$r->chunkId,'score'=>$r->score],$knowledge)]);
            $memory=$this->memory->recall($context,$definition->name); $toolCalls=0; $same=[];
            if($approvedCall!==null){
                $this->policies->enforce($definition->policies,$context,'tool',['tool'=>$approvedCall['tool'],'arguments'=>$approvedCall['arguments'],'approved'=>true]);
                $result=$this->tools->execute($runId,$registry,$approvedCall['tool'],$context,$approvedCall['arguments'],$approvedCall['approval_id']);
                $toolCalls++; $same[$approvedCall['tool']]=1;
                $this->runs->addStep($runId,StepType::ToolResult,['tool'=>$approvedCall['tool'],'arguments'=>$approvedCall['arguments'],'approval_id'=>$approvedCall['approval_id']],['success'=>$result->success,'ambiguous'=>$result->ambiguous,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence]);
                if($result->ambiguous){$this->runs->transition($runId,RunStatus::Ambiguous);return new AgentResult($runId,RunStatus::Ambiguous,null,$result->evidence,null,['message'=>$result->message]);}
                $transcript[]=['role'=>'tool','tool'=>$approvedCall['tool'],'success'=>$result->success,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence];
            }
            for($step=0;;$step++) {
                if($this->runs->isCancelled($runId)) return new AgentResult($runId,RunStatus::Cancelled);
                $this->budget->assertCanContinue($runId,$context,$step,$toolCalls,$this->usage->cost($runId));
                $request=new ModelRequest($this->prompts->system($definition,$registry->all()),$this->prompts->prompt($input,$knowledge,$memory,$transcript),$context,$definition->requiredCapabilities,['run_id'=>$runId],(int)config('agent-fabric.runtime.timeout',120));
                $profile=$this->router->route($request); $response=$this->gateway->generate($request,$profile); $this->usage->record($runId,$response);
                $this->runs->addStep($runId,StepType::ModelCall,['provider'=>$profile->provider,'model'=>$profile->model],['text'=>$response->text],['input_tokens'=>$response->inputTokens,'output_tokens'=>$response->outputTokens,'cost'=>$response->cost]);
                $envelope=$this->parser->parse($response->text); $transcript[]=['role'=>'model','envelope'=>['type'=>$envelope->type,'answer'=>$envelope->answer,'tool'=>$envelope->tool,'arguments'=>$envelope->arguments,'citations'=>$envelope->citations]];
                if($envelope->type==='tool') {
                    $toolCalls++; $same[$envelope->tool]=($same[$envelope->tool]??0)+1;
                    if($same[$envelope->tool]>(int)config('agent-fabric.budgets.max_same_tool_calls',3)) throw new \RuntimeException("Repeated tool loop detected for [{$envelope->tool}].");
                    $this->policies->enforce($definition->policies,$context,'tool',['tool'=>$envelope->tool,'arguments'=>$envelope->arguments]);
                    try {
                        $result=$this->tools->execute($runId,$registry,$envelope->tool,$context,$envelope->arguments,null);
                    } catch(ApprovalRequiredException $e) {
                        $this->runs->transition($runId,RunStatus::WaitingForApproval,['waiting_approval_id'=>$e->approvalId]);
                        return new AgentResult($runId,RunStatus::WaitingForApproval,null,[],null,['approval_id'=>$e->approvalId,'reason'=>$e->getMessage()]);
                    }
                    $this->runs->addStep($runId,StepType::ToolResult,['tool'=>$envelope->tool,'arguments'=>$envelope->arguments],['success'=>$result->success,'ambiguous'=>$result->ambiguous,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence]);
                    if($result->ambiguous){$this->runs->transition($runId,RunStatus::Ambiguous); return new AgentResult($runId,RunStatus::Ambiguous,null,$result->evidence,null,['message'=>$result->message]);}
                    $transcript[]=['role'=>'tool','tool'=>$envelope->tool,'success'=>$result->success,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence];
                    continue;
                }
                if(in_array($envelope->type,['clarify','escalate'],true)){
                    $status=$envelope->type==='clarify'?RunStatus::WaitingForUser:RunStatus::Completed; $this->runs->transition($runId,$status,['output'=>$envelope->answer]); return new AgentResult($runId,$status,$envelope->answer,[],null,['escalated'=>$envelope->type==='escalate']);
                }
                $evidence=$this->evidence($transcript,$knowledge,$envelope->citations); $verification=$this->verifier->verify($envelope->answer??'',$evidence,['tool_claims'=>$toolCalls>0,'knowledge_available'=>$knowledge!==[]]);
                $this->runs->addStep($runId,StepType::Verification,['answer'=>$envelope->answer],['status'=>$verification->status->value,'score'=>$verification->score,'issues'=>$verification->issues]);
                if(!$verification->passed()) { $transcript[]=['role'=>'verifier','issues'=>$verification->issues,'instruction'=>'Correct the answer using evidence; do not invent facts.']; continue; }
                foreach($envelope->memory as $item){if(is_array($item)&&isset($item['key'],$item['value']))$this->memory->remember($context,$definition->name,MemoryKind::Semantic,(string)$item['key'],$item['value'],(float)($item['confidence']??.8));}
                $this->runs->transition($runId,RunStatus::Completed,['output'=>$envelope->answer]); return new AgentResult($runId,RunStatus::Completed,$envelope->answer,$evidence,$verification,['provider'=>$profile->provider,'model'=>$profile->model]);
            }
        } catch(Throwable $e){
            $this->runs->transition($runId,RunStatus::Failed,['failure_code'=>$e::class,'failure_message'=>$e->getMessage()]); throw $e;
        }
    }

    private function resolveTools(array $items): ToolRegistry
    {
        $registry=new ToolRegistry; foreach($items as $item){$tool=is_string($item)?app($item):$item;if($tool instanceof \Evolvex\AgentFabric\Contracts\AgentTool)$registry->register($tool);} return $registry;
    }
    private function evidence(array $transcript,array $knowledge=[],array $citations=[]): array
    {
        $out=[];foreach($transcript as $entry)foreach(($entry['evidence']??[]) as $e)$out[]=is_scalar($e)?(string)$e:json_encode($e);
        $allowed=[];foreach($knowledge as $item)$allowed[$item->chunkId]=$item;
        foreach($citations as $chunkId)if(isset($allowed[$chunkId]))$out[]='knowledge:'.$allowed[$chunkId]->documentId.':'.$chunkId;
        return array_values(array_unique($out));
    }
    private function transcript(string $runId): array
    {
        $out=[];
        foreach($this->runs->steps($runId) as $s){
            $type=$s['type']??'';$i=json_decode((string)($s['input']??'{}'),true)?:[];$o=json_decode((string)($s['output']??'{}'),true)?:[];
            if($type===StepType::ModelCall->value && isset($o['text'])){
                try{$e=$this->parser->parse((string)$o['text']);$out[]=['role'=>'model','envelope'=>['type'=>$e->type,'answer'=>$e->answer,'tool'=>$e->tool,'arguments'=>$e->arguments,'citations'=>$e->citations]];}catch(\Throwable){}
            }elseif($type===StepType::ToolResult->value){
                $out[]=['role'=>'tool','tool'=>$i['tool']??null,'success'=>$o['success']??false,'data'=>$o['data']??null,'message'=>$o['message']??null,'evidence'=>$o['evidence']??[]];
            }elseif($type===StepType::Prompt->value && isset($i['follow_up'])){
                $out[]=['role'=>'user','content'=>$i['follow_up']];
            }
        }
        return $out;
    }

}
