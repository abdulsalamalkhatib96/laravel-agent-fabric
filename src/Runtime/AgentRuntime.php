<?php

namespace Evolvex\AgentFabric\Runtime;

use Evolvex\AgentFabric\Agents\AgentBlueprint;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\BudgetManager;
use Evolvex\AgentFabric\Contracts\ContextManager;
use Evolvex\AgentFabric\Contracts\LeaseManager;
use Evolvex\AgentFabric\Contracts\MemoryStore;
use Evolvex\AgentFabric\Contracts\ModelGateway;
use Evolvex\AgentFabric\Contracts\ModelRouter;
use Evolvex\AgentFabric\Contracts\PiiDetector;
use Evolvex\AgentFabric\Contracts\PromptInjectionDetector;
use Evolvex\AgentFabric\Contracts\Retriever;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Contracts\TraceRecorder;
use Evolvex\AgentFabric\Contracts\UsageMeter;
use Evolvex\AgentFabric\Contracts\Verifier;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AgentResult;
use Evolvex\AgentFabric\Data\ModelRequest;
use Evolvex\AgentFabric\Deployment\PromptVersionStore;
use Evolvex\AgentFabric\Enums\MemoryKind;
use Evolvex\AgentFabric\Enums\RunStatus;
use Evolvex\AgentFabric\Enums\StepType;
use Evolvex\AgentFabric\Exceptions\ApprovalRequiredException;
use Evolvex\AgentFabric\Exceptions\BudgetExceededException;
use Evolvex\AgentFabric\Memory\MemoryGovernanceService;
use Evolvex\AgentFabric\Policies\PolicyEngine;
use Evolvex\AgentFabric\Runtime\Protocol\EnvelopeParser;
use Evolvex\AgentFabric\Tools\ToolExecutor;
use Evolvex\AgentFabric\Tools\ToolRegistry;
use Illuminate\Support\Str;
use Throwable;

final class AgentRuntime
{
    public function __construct(
        private readonly ModelRouter $router,
        private readonly ModelGateway $gateway,
        private readonly Retriever $retriever,
        private readonly MemoryStore $memory,
        private readonly RunRepository $runs,
        private readonly UsageMeter $usage,
        private readonly BudgetManager $budget,
        private readonly Verifier $verifier,
        private readonly ToolExecutor $tools,
        private readonly PromptBuilder $prompts,
        private readonly EnvelopeParser $parser,
        private readonly PolicyEngine $policies,
        private readonly ApprovalManager $approvals,
        private readonly TraceRecorder $traces,
        private readonly LeaseManager $leases,
        private readonly ContextManager $contexts,
        private readonly MemoryGovernanceService $memoryGovernance,
        private readonly PiiDetector $pii,
        private readonly PromptInjectionDetector $injection,
        private readonly PromptVersionStore $promptVersions,
    ) {}

    public function run(AgentBlueprint $blueprint,AgentContext $context,string $input):AgentResult
    {
        $definition=$blueprint->definition();$runId=$this->runs->create($definition,$context,$input);
        return $this->execute($runId,$definition,$context,$input,[],null);
    }

    public function resume(AgentBlueprint $blueprint,AgentContext $context,string $runId,string $approvalId):AgentResult
    {
        $run=$this->requireRun($runId,$context);
        $approval=$this->approvals->find($approvalId);
        if(!$approval||!in_array(($approval['status']??null),['approved','rejected'],true))throw new \RuntimeException('Approval decision is still pending or missing.');
        if((string)($approval['run_id']??'')!==$runId||(string)($approval['tenant_id']??'')!==(string)$context->tenantId)throw new \RuntimeException('Approval does not belong to this run or tenant.');
        $transcript=$this->transcript($runId);$approvedCall=null;
        if(($approval['status']??null)==='approved')$approvedCall=['approval_id'=>$approvalId,'tool'=>(string)$approval['tool_name'],'arguments'=>$approval['arguments']??[]];
        else{$transcript[]=['role'=>'tool','tool'=>(string)$approval['tool_name'],'success'=>false,'message'=>'Human approval rejected: '.($approval['decision_reason']??'not approved'),'evidence'=>[]];$this->runs->addStep($runId,StepType::Approval,['approval_id'=>$approvalId],['approved'=>false,'reason'=>$approval['decision_reason']??null]);}
        return $this->execute($runId,$blueprint->definition(),$context,(string)$run['input'],$transcript,$approvedCall);
    }

    public function continueWithUser(AgentBlueprint $blueprint,AgentContext $context,string $runId,string $message):AgentResult
    {
        $run=$this->requireRun($runId,$context);
        if(($run['status']??null)!==RunStatus::WaitingForUser->value)throw new \RuntimeException('Run is not waiting for user input.');
        $transcript=$this->transcript($runId);$transcript[]=['role'=>'user','content'=>$message];
        $this->runs->addStep($runId,StepType::Prompt,['follow_up'=>$message],[]);
        return $this->execute($runId,$blueprint->definition(),$context,(string)$run['input']."\n\nUSER FOLLOW-UP:\n".$message,$transcript,null);
    }

    private function execute(string $runId,$definition,AgentContext $context,string $input,array $transcript,?array $approvedCall):AgentResult
    {
        $owner=(string)Str::uuid();$ttl=max(15,(int)config('agent-fabric.runtime.lease_seconds',90));
        if(!$this->leases->acquire('agent-run',$runId,$owner,$ttl))throw new \RuntimeException("Agent run [{$runId}] is already owned by another worker.");
        try{
            $traceId=$context->correlationId??$runId;$toolTrajectory=[];$started=microtime(true);
            $risk=$this->injection->inspect($input);
            if(($risk['risk']??'low')==='high'&&config('agent-fabric.security.block_high_risk_user_prompt_injection',false))throw new \RuntimeException('High-risk prompt-injection pattern detected in user input.');
            $this->traces->record($traceId,'agent','started',['run_id'=>$runId,'agent'=>$definition->name,'version'=>$definition->version,'input_injection_risk'=>$risk['risk']??'low']);
            $this->policies->enforce($definition->policies,$context,'start',['input'=>$input,'injection'=>$risk]);
            $this->runs->transition($runId,RunStatus::Running,['started_at'=>now()]);
            $registry=$this->resolveTools($definition->tools);
            $system=$this->prompts->system($definition,$registry->all());
            $this->promptVersions->register($definition->name,$definition->version,$system,'Agent Fabric dynamic prompt');
            if(config('agent-fabric.prompt_versions.use_active_override',false)){
                $active=$this->promptVersions->active($definition->name);
                if($active&&is_string($active['system_prompt']??null)&&$active['system_prompt']!=='')$system=(string)$active['system_prompt'];
            }

            $knowledge=$this->retriever->retrieve($input,$context,$definition->knowledgeSources,(int)config('agent-fabric.knowledge.default_limit',8));
            $this->runs->addStep($runId,StepType::Retrieve,['query'=>$input],['count'=>count($knowledge),'results'=>array_map(fn($r)=>['document_id'=>$r->documentId,'chunk_id'=>$r->chunkId,'score'=>$r->score,'injection_risk'=>$r->metadata['injection_risk']['risk']??'low'],$knowledge)]);
            $this->traces->record($traceId,'retrieval','completed',['run_id'=>$runId,'count'=>count($knowledge)]);
            $memory=$this->memory->recall($context,$definition->name);$toolCalls=0;$same=[];$malformedOutputs=0;

            if($approvedCall!==null){
                $this->policies->enforce($definition->policies,$context,'tool',['tool'=>$approvedCall['tool'],'arguments'=>$approvedCall['arguments'],'approved'=>true]);
                $result=$this->tools->execute($runId,$registry,$approvedCall['tool'],$context,$approvedCall['arguments'],$approvedCall['approval_id']);$toolCalls++;$same[$approvedCall['tool']]=1;$toolTrajectory[]=$approvedCall['tool'];
                $this->runs->addStep($runId,StepType::ToolResult,['tool'=>$approvedCall['tool'],'arguments'=>$approvedCall['arguments'],'approval_id'=>$approvedCall['approval_id']],['success'=>$result->success,'ambiguous'=>$result->ambiguous,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence]);
                if($result->ambiguous){$this->runs->transition($runId,RunStatus::Ambiguous);return new AgentResult($runId,RunStatus::Ambiguous,null,$result->evidence,null,['message'=>$result->message]);}
                $transcript[]=['role'=>'tool','tool'=>$approvedCall['tool'],'success'=>$result->success,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence];
            }

            for($step=0;;$step++){
                $this->leases->renew('agent-run',$runId,$owner,$ttl);
                if($this->runs->isCancelled($runId))return new AgentResult($runId,RunStatus::Cancelled);
                $this->budget->assertCanContinue($runId,$context,$step,$toolCalls,$this->usage->cost($runId));

                $safeInput=config('agent-fabric.security.redact_pii_before_model',false)?$this->pii->redact($input):$input;
                $routingRequest=new ModelRequest($system,$safeInput,$context,$definition->requiredCapabilities,['run_id'=>$runId],(int)config('agent-fabric.runtime.timeout',120));
                $profile=$this->router->route($routingRequest);
                $compact=$this->contexts->compact($system,$safeInput,$knowledge,$memory,$transcript,$context,$profile->contextWindow);
                $request=new ModelRequest($system,$this->prompts->prompt($safeInput,$compact['knowledge'],$compact['memory'],$compact['transcript']),$context,$definition->requiredCapabilities,['run_id'=>$runId,'context_tokens'=>$compact['estimated_tokens'],'context_budget'=>$compact['budget_tokens']],(int)config('agent-fabric.runtime.timeout',120));
                $modelStarted=microtime(true);$response=$this->gateway->generate($request,$profile);$latency=(microtime(true)-$modelStarted)*1000;$this->usage->record($runId,$response);
                $this->traces->record($traceId,'model','completed',['run_id'=>$runId,'provider'=>$response->provider??$profile->provider,'model'=>$response->model??$profile->model,'input_tokens'=>$response->inputTokens,'output_tokens'=>$response->outputTokens,'cost'=>$response->cost,'latency_ms'=>$latency,'context_tokens'=>$compact['estimated_tokens']],$latency);
                $this->runs->addStep($runId,StepType::ModelCall,['provider'=>$response->provider??$profile->provider,'model'=>$response->model??$profile->model],['text'=>$response->text],['input_tokens'=>$response->inputTokens,'output_tokens'=>$response->outputTokens,'cost'=>$response->cost,'latency_ms'=>$latency]);
                try{$envelope=$this->parser->parse($response->text);$malformedOutputs=0;}catch(Throwable $parseError){
                    $malformedOutputs++;$this->traces->record($traceId,'model','malformed_output',['run_id'=>$runId,'provider'=>$response->provider??$profile->provider,'model'=>$response->model??$profile->model,'attempt'=>$malformedOutputs,'message'=>$parseError->getMessage()]);
                    $this->runs->addStep($runId,StepType::Verification,['kind'=>'model_envelope','attempt'=>$malformedOutputs],['status'=>'malformed','issues'=>[$parseError->getMessage()]]);
                    if($malformedOutputs>(int)config('agent-fabric.runtime.max_malformed_outputs',2))throw new \RuntimeException('Model repeatedly returned malformed Agent Fabric envelopes.',$parseError->getCode(),$parseError);
                    $transcript[]=['role'=>'runtime','instruction'=>'Your previous response was invalid. Return exactly one valid Agent Fabric JSON envelope matching the required schema. Do not add prose outside JSON.','error'=>$parseError->getMessage()];continue;
                }
                $transcript[]=['role'=>'model','envelope'=>['type'=>$envelope->type,'answer'=>$envelope->answer,'tool'=>$envelope->tool,'arguments'=>$envelope->arguments,'citations'=>$envelope->citations]];

                if($envelope->type==='tool'){
                    $toolCalls++;$same[$envelope->tool]=($same[$envelope->tool]??0)+1;$toolTrajectory[]=$envelope->tool;
                    if($same[$envelope->tool]>(int)config('agent-fabric.budgets.max_same_tool_calls',3))throw new BudgetExceededException("Repeated tool loop detected for [{$envelope->tool}].");
                    $this->policies->enforce($definition->policies,$context,'tool',['tool'=>$envelope->tool,'arguments'=>$envelope->arguments]);
                    try{$result=$this->tools->execute($runId,$registry,$envelope->tool,$context,$envelope->arguments,null);}catch(ApprovalRequiredException $e){$this->runs->transition($runId,RunStatus::WaitingForApproval,['waiting_approval_id'=>$e->approvalId]);return new AgentResult($runId,RunStatus::WaitingForApproval,null,[],null,['approval_id'=>$e->approvalId,'reason'=>$e->getMessage()]);}
                    $this->runs->addStep($runId,StepType::ToolResult,['tool'=>$envelope->tool,'arguments'=>$envelope->arguments],['success'=>$result->success,'ambiguous'=>$result->ambiguous,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence]);
                    $this->traces->record($traceId,'tool','completed',['run_id'=>$runId,'tool'=>$envelope->tool,'success'=>$result->success,'ambiguous'=>$result->ambiguous]);
                    if($result->ambiguous){$this->runs->transition($runId,RunStatus::Ambiguous);return new AgentResult($runId,RunStatus::Ambiguous,null,$result->evidence,null,['message'=>$result->message]);}
                    $transcript[]=['role'=>'tool','tool'=>$envelope->tool,'success'=>$result->success,'data'=>$result->data,'message'=>$result->message,'evidence'=>$result->evidence];continue;
                }

                if(in_array($envelope->type,['clarify','escalate'],true)){
                    $status=$envelope->type==='clarify'?RunStatus::WaitingForUser:RunStatus::Completed;$this->runs->transition($runId,$status,['output'=>$envelope->answer]);return new AgentResult($runId,$status,$envelope->answer,[],null,['escalated'=>$envelope->type==='escalate']);
                }

                $evidence=$this->evidence($transcript,$compact['knowledge'],$envelope->citations);$verification=$this->verifier->verify($envelope->answer??'',$evidence,['tool_claims'=>$toolCalls>0,'knowledge_available'=>$compact['knowledge']!==[]]);
                $this->runs->addStep($runId,StepType::Verification,['answer'=>$envelope->answer],['status'=>$verification->status->value,'score'=>$verification->score,'issues'=>$verification->issues]);
                if(!$verification->passed()){$transcript[]=['role'=>'verifier','issues'=>$verification->issues,'instruction'=>'Correct the answer using evidence; do not invent facts.'];continue;}

                foreach($envelope->memory as $item){
                    if(!is_array($item)||!isset($item['key'],$item['value']))continue;
                    $confidence=(float)($item['confidence']??.8);
                    if(config('agent-fabric.memory.auto_approve',false))$this->memory->remember($context,$definition->name,MemoryKind::Semantic,(string)$item['key'],$item['value'],$confidence);
                    else $this->memoryGovernance->propose($context,$definition->name,(string)$item['key'],$item['value'],$confidence);
                }
                $this->runs->transition($runId,RunStatus::Completed,['output'=>$envelope->answer]);
                $this->traces->record($traceId,'agent','completed',['run_id'=>$runId,'verification'=>$verification->status->value,'duration_ms'=>(microtime(true)-$started)*1000]);
                return new AgentResult($runId,RunStatus::Completed,$envelope->answer,$evidence,$verification,['provider'=>$response->provider??$profile->provider,'model'=>$response->model??$profile->model,'tools'=>$toolTrajectory]);
            }
        }catch(BudgetExceededException $e){$this->runs->transition($runId,RunStatus::Exhausted,['failure_code'=>$e::class,'failure_message'=>$e->getMessage()]);if(isset($traceId))$this->traces->record($traceId,'agent','exhausted',['run_id'=>$runId,'message'=>$e->getMessage()]);return new AgentResult($runId,RunStatus::Exhausted,null,[],null,['message'=>$e->getMessage()]);}
        catch(Throwable $e){$this->runs->transition($runId,RunStatus::Failed,['failure_code'=>$e::class,'failure_message'=>$e->getMessage()]);if(isset($traceId))$this->traces->record($traceId,'agent','failed',['run_id'=>$runId,'exception'=>$e::class,'message'=>$e->getMessage()]);throw $e;}
        finally{$this->leases->release('agent-run',$runId,$owner);}
    }

    private function requireRun(string $runId,AgentContext $context):array
    {
        $run=$this->runs->get($runId);if(!$run)throw new \InvalidArgumentException("Unknown run [{$runId}].");if((string)$run['tenant_id']!==(string)$context->tenantId)throw new \RuntimeException('Cross-tenant run access denied.');return $run;
    }
    private function resolveTools(array $items):ToolRegistry{$registry=new ToolRegistry;foreach($items as $item){$tool=is_string($item)?app($item):$item;if($tool instanceof \Evolvex\AgentFabric\Contracts\AgentTool)$registry->register($tool);}return $registry;}
    private function evidence(array $transcript,array $knowledge=[],array $citations=[]):array{$out=[];foreach($transcript as $entry)foreach(($entry['evidence']??[]) as $e)$out[]=is_scalar($e)?(string)$e:json_encode($e);$allowed=[];foreach($knowledge as $item)$allowed[$item->chunkId]=$item;foreach($citations as $chunkId)if(isset($allowed[$chunkId]))$out[]='knowledge:'.$allowed[$chunkId]->documentId.':'.$chunkId;return array_values(array_unique($out));}
    private function transcript(string $runId):array
    {
        $out=[];foreach($this->runs->steps($runId) as $s){$type=$s['type']??'';$i=json_decode((string)($s['input']??'{}'),true)?:[];$o=json_decode((string)($s['output']??'{}'),true)?:[];
            if($type===StepType::ModelCall->value&&isset($o['text'])){try{$e=$this->parser->parse((string)$o['text']);$out[]=['role'=>'model','envelope'=>['type'=>$e->type,'answer'=>$e->answer,'tool'=>$e->tool,'arguments'=>$e->arguments,'citations'=>$e->citations]];}catch(Throwable){}}
            elseif($type===StepType::ToolResult->value)$out[]=['role'=>'tool','tool'=>$i['tool']??null,'success'=>$o['success']??false,'data'=>$o['data']??null,'message'=>$o['message']??null,'evidence'=>$o['evidence']??[]];
            elseif($type===StepType::Prompt->value&&isset($i['follow_up']))$out[]=['role'=>'user','content'=>$i['follow_up']];
        }return $out;
    }
}
