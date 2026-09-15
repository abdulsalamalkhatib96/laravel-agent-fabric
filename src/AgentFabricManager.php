<?php

namespace Evolvex\AgentFabric;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Enums\RunStatus;
use Evolvex\AgentFabric\Feedback\FeedbackRecorder;
use Evolvex\AgentFabric\Runtime\AgentRuntime;

final class AgentFabricManager
{
    public function __construct(
        private readonly AgentRegistry $agents,
        private readonly AgentRuntime $runtime,
        private readonly ApprovalManager $approvals,
        private readonly RunRepository $runs,
        private readonly FeedbackRecorder $feedback,
    ) {}

    public function agent(string $name): PendingAgentRun
    {
        return new PendingAgentRun($this->agents->get($name), $this->runtime);
    }

    public function registry(): AgentRegistry
    {
        return $this->agents;
    }

    public function approve(string $approvalId, string|int|null $decidedBy = null, ?string $reason = null): void
    {
        $this->approvals->decide($approvalId, true, $decidedBy, $reason);
    }

    public function reject(string $approvalId, string|int|null $decidedBy = null, ?string $reason = null): void
    {
        $this->approvals->decide($approvalId, false, $decidedBy, $reason);
    }

    public function cancel(string $runId): void
    {
        $this->runs->transition($runId, RunStatus::Cancelled);
    }

    public function feedback(string $runId, string|int $tenantId, ?int $rating = null, ?string $label = null, ?string $reason = null, ?string $correctedAnswer = null): string
    {
        return $this->feedback->record($runId, $tenantId, $rating, $label, $reason, $correctedAnswer);
    }
}
