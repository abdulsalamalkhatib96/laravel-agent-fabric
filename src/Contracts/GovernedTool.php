<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolExecutionPolicy;
use Evolvex\AgentFabric\Enums\ToolKind;

interface GovernedTool extends AgentTool
{
    public function kind(): ToolKind;
    public function executionPolicy(): ToolExecutionPolicy;
    /** @return list<string> */
    public function sideEffects(): array;
    public function before(AgentContext $context, array $arguments): void;
    public function after(AgentContext $context, array $arguments, mixed $result): void;
}
