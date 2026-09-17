<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RemoteOperationOutcome;

interface RemoteOperationReconciler
{
    public function reconcile(string $operationName, string $reconciliationKey, AgentContext $context, array $metadata = []): RemoteOperationOutcome;
}
