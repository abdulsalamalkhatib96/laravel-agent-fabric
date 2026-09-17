<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Contracts\RichTool;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolExecutionPolicy;
use Evolvex\AgentFabric\Enums\ToolKind;

abstract class AbstractGovernedTool implements RichTool
{
    public function kind(): ToolKind { return ToolKind::Command; }
    public function executionPolicy(): ToolExecutionPolicy { return new ToolExecutionPolicy; }
    public function sideEffects(): array { return []; }
    public function outputSchema(): array { return []; }
    public function declaredErrors(): array { return []; }
    public function before(AgentContext $context, array $arguments): void {}
    public function after(AgentContext $context, array $arguments, mixed $result): void {}
}
