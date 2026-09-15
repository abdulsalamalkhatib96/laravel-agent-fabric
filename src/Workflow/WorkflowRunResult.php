<?php

namespace Evolvex\AgentFabric\Workflow;

use Evolvex\AgentFabric\Enums\WorkflowStatus;

final readonly class WorkflowRunResult
{
    public function __construct(public string $runId, public WorkflowStatus $status, public array $state=[], public array $steps=[], public ?string $message=null) {}
}
