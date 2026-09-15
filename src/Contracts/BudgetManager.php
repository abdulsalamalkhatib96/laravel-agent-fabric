<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;

interface BudgetManager
{
    public function assertCanContinue(string $runId, AgentContext $context, int $step, int $toolCalls, float $cost): void;
}
