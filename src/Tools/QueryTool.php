<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Enums\ToolKind;
use Evolvex\AgentFabric\Data\ToolExecutionPolicy;

abstract class QueryTool extends AbstractGovernedTool
{
    public function kind(): ToolKind { return ToolKind::Query; }
    public function executionPolicy(): ToolExecutionPolicy { return new ToolExecutionPolicy(timeoutSeconds: 20, maxAttempts: 2, idempotent: true, allowAutomaticRetry: true); }
}
