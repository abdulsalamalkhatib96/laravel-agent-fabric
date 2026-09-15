<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Enums\ToolKind;
use Evolvex\AgentFabric\Data\ToolExecutionPolicy;

abstract class RemoteOperationTool extends AbstractGovernedTool
{
    public function kind(): ToolKind { return ToolKind::RemoteOperation; }
    public function executionPolicy(): ToolExecutionPolicy { return new ToolExecutionPolicy(timeoutSeconds: 30, maxAttempts: 1, idempotent: true, allowAutomaticRetry: false, requiresReconciliationOnAmbiguity: true); }
}
