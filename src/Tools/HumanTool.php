<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Enums\ToolKind;

abstract class HumanTool extends AbstractGovernedTool
{
    public function kind(): ToolKind { return ToolKind::Human; }
}
