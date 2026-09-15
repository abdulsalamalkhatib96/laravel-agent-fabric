<?php

namespace Evolvex\AgentFabric\Workflow;

use Evolvex\AgentFabric\Contracts\Workflow;
use InvalidArgumentException;

final class WorkflowRegistry
{
    private array $workflows=[];
    public function register(Workflow|string $workflow): self { $instance=is_string($workflow)?app($workflow):$workflow; $this->workflows[$instance->name()]=$instance; return $this; }
    public function get(string $name): Workflow { return $this->workflows[$name]??throw new InvalidArgumentException("Unknown workflow [{$name}]."); }
    public function all(): array { return $this->workflows; }
}
