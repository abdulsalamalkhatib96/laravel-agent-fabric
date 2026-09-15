<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Enums\ToolKind;

abstract class CommandTool extends AbstractGovernedTool
{
    public function kind(): ToolKind { return ToolKind::Command; }
}
