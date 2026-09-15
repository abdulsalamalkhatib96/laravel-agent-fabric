<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Enums\ToolKind;

abstract class WorkflowTool extends AbstractGovernedTool
{
    public function kind(): ToolKind { return ToolKind::Workflow; }
}
