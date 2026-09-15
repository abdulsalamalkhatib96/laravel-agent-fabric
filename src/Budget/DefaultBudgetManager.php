<?php

namespace Evolvex\AgentFabric\Budget;

use Evolvex\AgentFabric\Contracts\BudgetManager;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Exceptions\BudgetExceededException;

final class DefaultBudgetManager implements BudgetManager
{
    public function assertCanContinue(string $runId, AgentContext $context, int $step, int $toolCalls, float $cost): void
    {
        if ($step >= (int) config('agent-fabric.runtime.max_steps',20)) throw new BudgetExceededException('Maximum agent steps exceeded.');
        if ($toolCalls >= (int) config('agent-fabric.budgets.max_tool_calls',15)) throw new BudgetExceededException('Maximum tool calls exceeded.');
        if ($cost >= (float) config('agent-fabric.budgets.max_cost_per_run',1.0)) throw new BudgetExceededException('Maximum run cost exceeded.');
    }
}
