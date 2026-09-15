<?php

namespace Evolvex\AgentFabric\Approvals;

use Evolvex\AgentFabric\Contracts\AgentTool;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ApprovalDecision;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseApprovalManager implements ApprovalManager
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function evaluate(AgentTool $tool, AgentContext $context, array $arguments): ApprovalDecision
    {
        return $tool->risk()->requiresApprovalByDefault() ? ApprovalDecision::required('High-risk tool requires human approval.') : ApprovalDecision::notRequired();
    }
    public function request(string $runId, AgentTool $tool, AgentContext $context, array $arguments, string $reason): string
    {
        $id=(string)Str::uuid();
        $this->db->table('ai_approvals')->insert(['id'=>$id,'run_id'=>$runId,'tenant_id'=>(string)$context->tenantId,'tool_name'=>$tool->name(),'arguments'=>json_encode($arguments),'reason'=>$reason,'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        return $id;
    }
    public function approved(string $approvalId): bool { return $this->db->table('ai_approvals')->where('id',$approvalId)->value('status')==='approved'; }
    public function decide(string $approvalId, bool $approved, string|int|null $decidedBy = null, ?string $reason = null): void
    {
        $this->db->table('ai_approvals')->where('id',$approvalId)->where('status','pending')->update(['status'=>$approved?'approved':'rejected','decided_by'=>$decidedBy===null?null:(string)$decidedBy,'decision_reason'=>$reason,'decided_at'=>now(),'updated_at'=>now()]);
    }
    public function find(string $approvalId): ?array
    {
        $r=$this->db->table('ai_approvals')->where('id',$approvalId)->first();
        if (!$r) return null; $a=(array)$r; $a['arguments']=json_decode((string)($a['arguments']??'{}'),true)?:[]; return $a;
    }
}
