<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\ToolAuthorizer;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Exceptions\ApprovalRequiredException;
use Evolvex\AgentFabric\Exceptions\ToolAuthorizationException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Throwable;

final class ToolExecutor
{
    public function __construct(private readonly ToolAuthorizer $authorizer, private readonly ApprovalManager $approvals, private readonly ConnectionInterface $db, private readonly SchemaValidator $validator) {}

    public function execute(string $runId, ToolRegistry $registry, string $toolName, AgentContext $context, array $arguments, ?string $approvalId = null): ToolResult
    {
        $tool=$registry->get($toolName); $this->validator->validate($tool->inputSchema(),$arguments);
        $auth=$this->authorizer->authorize($tool,$context,$arguments); if(!$auth->allowed) throw new ToolAuthorizationException($auth->reason??'Tool call denied.');
        if ($approvalId===null) {
            $approval=$this->approvals->evaluate($tool,$context,$arguments);
            if($approval->required){$id=$this->approvals->request($runId,$tool,$context,$arguments,$approval->reason??'Approval required.'); throw new ApprovalRequiredException($id,$approval->reason??'Approval required.');}
        } else {
            $record = $this->approvals->find($approvalId);
            if (! $record || ($record['status'] ?? null) !== 'approved') throw new ToolAuthorizationException('Approval has not been granted.');
            if ((string) ($record['run_id'] ?? '') !== $runId || (string) ($record['tenant_id'] ?? '') !== (string) $context->tenantId)
                throw new ToolAuthorizationException('Approval does not belong to this run or tenant.');
            if (($record['tool_name'] ?? null) !== $toolName || $this->canonical($record['arguments'] ?? []) !== $this->canonical($arguments))
                throw new ToolAuthorizationException('Approval does not match the requested tool call.');
        }

        $canonicalArguments=$this->canonical($arguments);
        $key=hash('sha256',$runId.'|'.$toolName.'|'.$canonicalArguments);
        $id=(string)Str::uuid();
        $inserted=$this->db->table('ai_tool_executions')->insertOrIgnore(['id'=>$id,'run_id'=>$runId,'tenant_id'=>(string)$context->tenantId,'tool_name'=>$toolName,'arguments'=>$canonicalArguments,'idempotency_key'=>$key,'status'=>'executing','created_at'=>now(),'updated_at'=>now()]);
        if(!$inserted){
            $existing=$this->db->table('ai_tool_executions')->where('idempotency_key',$key)->first();
            if($existing && $existing->status==='succeeded') return new ToolResult(true,json_decode((string)$existing->result,true),$existing->message,false,json_decode((string)($existing->evidence??'[]'),true)?:[]);
            if($existing && in_array($existing->status,['executing','ambiguous'],true)) return ToolResult::ambiguous('An identical tool call is already executing or has an ambiguous result.');
            if(!$existing) return ToolResult::ambiguous('Could not establish idempotent ownership for the tool call.');
            $claimed=$this->db->table('ai_tool_executions')->where('id',$existing->id)->where('status','failed')->update(['status'=>'executing','message'=>null,'updated_at'=>now()]);
            if($claimed!==1) return ToolResult::ambiguous('Another worker claimed the retry.');
            $id=(string)$existing->id;
        }
        try {
            $result=$tool->execute($context,$arguments); $status=$result->ambiguous?'ambiguous':($result->success?'succeeded':'failed');
            $this->db->table('ai_tool_executions')->where('id',$id)->update(['status'=>$status,'result'=>json_encode($result->data),'message'=>$result->message,'evidence'=>json_encode($result->evidence),'finished_at'=>now(),'updated_at'=>now()]); return $result;
        } catch(Throwable $e){
            $this->db->table('ai_tool_executions')->where('id',$id)->update(['status'=>'failed','message'=>$e->getMessage(),'finished_at'=>now(),'updated_at'=>now()]); throw $e;
        }
    }

    private function canonical(array $value): string
    {
        $sort = function (&$item) use (&$sort): void {
            if (! is_array($item)) return;
            if (! array_is_list($item)) ksort($item);
            foreach ($item as &$child) $sort($child);
        };
        $sort($value);
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
