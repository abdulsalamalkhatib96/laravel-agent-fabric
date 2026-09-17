<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\Outbox;
use Evolvex\AgentFabric\Audit\AuditLogger;
use Evolvex\AgentFabric\Contracts\CircuitBreaker;
use Evolvex\AgentFabric\Contracts\LeaseManager;
use Evolvex\AgentFabric\Contracts\QuotaManager;
use Evolvex\AgentFabric\Contracts\RichTool;
use Evolvex\AgentFabric\Contracts\ToolAuthorizer;
use Evolvex\AgentFabric\Contracts\GovernedTool;
use Evolvex\AgentFabric\Enums\ToolKind;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Exceptions\ApprovalRequiredException;
use Evolvex\AgentFabric\Exceptions\ToolAuthorizationException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Throwable;

final class ToolExecutor
{
    public function __construct(
        private readonly ToolAuthorizer $authorizer,
        private readonly ApprovalManager $approvals,
        private readonly ConnectionInterface $db,
        private readonly SchemaValidator $validator,
        private readonly LeaseManager $leases,
        private readonly CircuitBreaker $breakers,
        private readonly QuotaManager $quotas,
        private readonly Outbox $outbox,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(string $runId, ToolRegistry $registry, string $toolName, AgentContext $context, array $arguments, ?string $approvalId = null): ToolResult
    {
        $tool=$registry->get($toolName);
        $this->validator->validate($tool->inputSchema(),$arguments);
        $tenant=(string)$context->tenantId;
        if(!$this->quotas->consume('tool_calls',$tenant.':'.$toolName,1))throw new ToolAuthorizationException("Tool quota exceeded for [{$toolName}].");
        $breakerKey='tool:'.$tenant.':'.$toolName;
        if(!$this->breakers->allow($breakerKey))throw new ToolAuthorizationException("Circuit breaker is open for [{$toolName}].");

        $executionContext=$context->with(['run_id'=>$runId]);
        if($tool instanceof GovernedTool){
            $tool->before($executionContext,$arguments);
            if(($context->metadata['execution_mode']??'live')==='simulate' && in_array($tool->kind(),[ToolKind::Command,ToolKind::RemoteOperation],true)){
                return ToolResult::success(['simulated'=>true,'tool'=>$toolName,'arguments'=>$arguments],'Simulation mode: side effect not executed.',[]);
            }
        }
        $this->audit->log($context,'tool.requested',['tool'=>$toolName,'arguments'=>$arguments],$runId);
        $auth=$this->authorizer->authorize($tool,$context,$arguments);
        if(!$auth->allowed)throw new ToolAuthorizationException($auth->reason??'Tool call denied.');
        $this->assertApproval($runId,$tool,$toolName,$context,$arguments,$approvalId);

        $canonicalArguments=$this->canonical($arguments);
        $key=hash('sha256',$runId.'|'.$toolName.'|'.$canonicalArguments);
        $id=(string)Str::uuid();
        $inserted=$this->db->table('ai_tool_executions')->insertOrIgnore([
            'id'=>$id,'run_id'=>$runId,'tenant_id'=>$tenant,'tool_name'=>$toolName,'arguments'=>$canonicalArguments,
            'idempotency_key'=>$key,'status'=>'executing','created_at'=>now(),'updated_at'=>now(),
        ]);
        if(!$inserted){
            $existing=$this->db->table('ai_tool_executions')->where('idempotency_key',$key)->first();
            if($existing && $existing->status==='succeeded')return new ToolResult(true,json_decode((string)$existing->result,true),$existing->message,false,json_decode((string)($existing->evidence??'[]'),true)?:[]);
            if($existing && in_array($existing->status,['executing','ambiguous'],true))return ToolResult::ambiguous('An identical tool call is already executing or has an ambiguous result.');
            if(!$existing)return ToolResult::ambiguous('Could not establish idempotent ownership for the tool call.');
            $claimed=$this->db->table('ai_tool_executions')->where('id',$existing->id)->where('status','failed')->update(['status'=>'executing','message'=>null,'updated_at'=>now()]);
            if($claimed!==1)return ToolResult::ambiguous('Another worker claimed the retry.');
            $id=(string)$existing->id;
        }

        $policy=$tool instanceof GovernedTool?$tool->executionPolicy():null;
        $owner=(string)Str::uuid();
        $leaseKey=$this->concurrencyKey($toolName,$arguments,$policy?->concurrencyKey);
        if($leaseKey!==null && !$this->leases->acquire('tool',$leaseKey,$owner,max(5,$policy?->timeoutSeconds??30))){
            $this->db->table('ai_tool_executions')->where('id',$id)->update(['status'=>'ambiguous','message'=>'Another worker owns the tool concurrency lease.','updated_at'=>now()]);
            return ToolResult::ambiguous('Another worker owns the tool concurrency lease.');
        }

        $maxAttempts=max(1,$policy?->maxAttempts??1);
        if(!($policy?->idempotent??true)||!($policy?->allowAutomaticRetry??false))$maxAttempts=1;
        try{
            for($attempt=1;$attempt<=$maxAttempts;$attempt++){
                try{
                    $result=$tool->execute($executionContext,$arguments);
                    if($tool instanceof RichTool && $tool->outputSchema()!==[])$this->validator->validate($tool->outputSchema(),$result->data,'$.result');
                    if($tool instanceof GovernedTool)$tool->after($executionContext,$arguments,$result);
                    $status=$result->ambiguous?'ambiguous':($result->success?'succeeded':'failed');
                    $this->db->transaction(function()use($id,$status,$result,$runId,$tenant,$toolName):void{
                        $this->db->table('ai_tool_executions')->where('id',$id)->update(['status'=>$status,'result'=>json_encode($result->data),'message'=>$result->message,'evidence'=>json_encode($result->evidence),'finished_at'=>now(),'updated_at'=>now()]);
                        $this->outbox->add('agent-fabric.tool.executed',['execution_id'=>$id,'run_id'=>$runId,'tool'=>$toolName,'status'=>$status,'evidence'=>$result->evidence],'tool:'.$id.':'.$status,$tenant);
                    });
                    $this->audit->log($context,'tool.'.$status,['tool'=>$toolName,'execution_id'=>$id,'message'=>$result->message,'evidence'=>$result->evidence],$runId);
                    if($result->success)$this->breakers->success($breakerKey);else $this->breakers->failure($breakerKey,$result->message);
                    return $result;
                }catch(Throwable $e){
                    $final=$attempt>=$maxAttempts;
                    if(!$final){usleep(min(1_000_000,100_000*(2**($attempt-1))));continue;}
                    if(($policy?->requiresReconciliationOnAmbiguity??false)){
                        $this->db->transaction(function()use($id,$e,$runId,$tenant,$toolName):void{
                            $this->db->table('ai_tool_executions')->where('id',$id)->update(['status'=>'ambiguous','message'=>$e->getMessage(),'finished_at'=>now(),'updated_at'=>now()]);
                            $this->outbox->add('agent-fabric.tool.ambiguous',['execution_id'=>$id,'run_id'=>$runId,'tool'=>$toolName,'error'=>$e->getMessage()],'tool:'.$id.':ambiguous',$tenant);
                        });
                        $this->audit->log($context,'tool.ambiguous',['tool'=>$toolName,'execution_id'=>$id,'error'=>$e->getMessage()],$runId);
                        $this->breakers->failure($breakerKey,$e->getMessage());
                        return ToolResult::ambiguous('Remote operation outcome is unknown and requires reconciliation.',['exception'=>$e::class,'message'=>$e->getMessage()]);
                    }
                    $this->db->transaction(function()use($id,$e,$runId,$tenant,$toolName):void{
                        $this->db->table('ai_tool_executions')->where('id',$id)->update(['status'=>'failed','message'=>$e->getMessage(),'finished_at'=>now(),'updated_at'=>now()]);
                        $this->outbox->add('agent-fabric.tool.failed',['execution_id'=>$id,'run_id'=>$runId,'tool'=>$toolName,'error'=>$e->getMessage()],'tool:'.$id.':failed',$tenant);
                    });
                    $this->audit->log($context,'tool.failed',['tool'=>$toolName,'execution_id'=>$id,'error'=>$e->getMessage()],$runId);
                    $this->breakers->failure($breakerKey,$e->getMessage());
                    throw $e;
                }
            }
            throw new \LogicException('Unreachable tool execution state.');
        } finally {
            if($leaseKey!==null)$this->leases->release('tool',$leaseKey,$owner);
        }
    }

    private function assertApproval(string $runId,object $tool,string $toolName,AgentContext $context,array $arguments,?string $approvalId):void
    {
        if($approvalId===null){
            $approval=$this->approvals->evaluate($tool,$context,$arguments);
            if($approval->required){$id=$this->approvals->request($runId,$tool,$context,$arguments,$approval->reason??'Approval required.');throw new ApprovalRequiredException($id,$approval->reason??'Approval required.');}
            return;
        }
        $record=$this->approvals->find($approvalId);
        if(!$record||($record['status']??null)!=='approved')throw new ToolAuthorizationException('Approval has not been granted.');
        if((string)($record['run_id']??'')!==$runId||(string)($record['tenant_id']??'')!==(string)$context->tenantId)throw new ToolAuthorizationException('Approval does not belong to this run or tenant.');
        if(($record['tool_name']??null)!==$toolName||$this->canonical($record['arguments']??[])!==$this->canonical($arguments))throw new ToolAuthorizationException('Approval does not match the requested tool call.');
    }

    private function concurrencyKey(string $tool,array $arguments,?string $template):?string
    {
        if($template===null||$template==='')return null;
        $resolved=preg_replace_callback('/\{([A-Za-z0-9_.-]+)\}/',function($m)use($arguments){$v=$arguments[$m[1]]??null;return is_scalar($v)?(string)$v:hash('sha256',json_encode($v));},$template);
        return $tool.':'.$resolved;
    }

    private function canonical(array $value):string
    {
        $sort=function(&$item)use(&$sort):void{if(!is_array($item))return;if(!array_is_list($item))ksort($item);foreach($item as &$child)$sort($child);};
        $sort($value);
        return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}
