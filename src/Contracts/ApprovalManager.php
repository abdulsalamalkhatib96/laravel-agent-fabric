<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ApprovalDecision;

interface ApprovalManager
{
    public function evaluate(AgentTool $tool, AgentContext $context, array $arguments): ApprovalDecision;
    public function request(string $runId, AgentTool $tool, AgentContext $context, array $arguments, string $reason): string;
    public function approved(string $approvalId): bool;
    public function decide(string $approvalId, bool $approved, string|int|null $decidedBy = null, ?string $reason = null): void;
    public function find(string $approvalId): ?array;
}
