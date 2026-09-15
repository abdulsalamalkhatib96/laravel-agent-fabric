<?php

namespace Evolvex\AgentFabric\Workflow;

use Evolvex\AgentFabric\Enums\WorkflowStepType;

final readonly class WorkflowStep
{
    public function __construct(
        public string $name,
        public WorkflowStepType $type,
        public ?string $handler = null,
        public array $dependsOn = [],
        public array $input = [],
        public ?string $compensation = null,
        public array $metadata = [],
    ) {}
}
