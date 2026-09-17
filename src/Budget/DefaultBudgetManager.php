<?php

namespace Evolvex\AgentFabric\Budget;

use Evolvex\AgentFabric\Contracts\BudgetManager;
use Evolvex\AgentFabric\Contracts\QuotaManager;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Exceptions\BudgetExceededException;
use Illuminate\Database\ConnectionInterface;

final class DefaultBudgetManager implements BudgetManager
{
    public function __construct(private readonly QuotaManager $quotas, private readonly ConnectionInterface $db) {}

    public function assertCanContinue(string $runId, AgentContext $context, int $step, int $toolCalls, float $cost): void
    {
        if ($step >= (int) config('agent-fabric.runtime.max_steps',20)) throw new BudgetExceededException('Maximum agent steps exceeded.');
        if ($toolCalls >= (int) config('agent-fabric.budgets.max_tool_calls',15)) throw new BudgetExceededException('Maximum tool calls exceeded.');
        if ($cost >= (float) config('agent-fabric.budgets.max_cost_per_run',1.0)) throw new BudgetExceededException('Maximum run cost exceeded.');

        $tenant=(string)$context->tenantId;
        if(! $this->quotas->consume('tenant_steps',$tenant,1)) throw new BudgetExceededException('Tenant execution rate limit exceeded.');
        if($context->actorId!==null && ! $this->quotas->consume('actor_steps',$tenant.':'.$context->actorId,1)) throw new BudgetExceededException('Actor execution rate limit exceeded.');
        $run=$this->db->table('ai_runs')->where('id',$runId)->first();
        if($run && ! $this->quotas->consume('agent_steps',$tenant.':'.$run->agent,1)) throw new BudgetExceededException('Agent execution rate limit exceeded.');
        $this->assertCostBudget($tenant,'tenant',$tenant,$cost);
        if($run)$this->assertCostBudget($tenant,'agent',(string)$run->agent,$cost);
    }

    private function assertCostBudget(string $tenantId,string $scope,string $key,float $runCost):void
    {
        $row=$this->db->table('ai_cost_budgets')->where('tenant_id',$tenantId)->where('scope',$scope)->where('scope_key',$key)->first();
        if(!$row)return;
        $periodStart=$this->periodStart((string)$row->period);
        if(strtotime((string)$row->period_started_at)<$periodStart->getTimestamp()){
            $this->db->table('ai_cost_budgets')->where('id',$row->id)->update(['spent'=>0,'period_started_at'=>$periodStart,'updated_at'=>now()]);
            $row->spent=0;
        }
        if(((float)$row->spent+$runCost)>=(float)$row->hard_limit)throw new BudgetExceededException("Hard cost budget exceeded for {$scope} [{$key}].");
    }

    private function periodStart(string $period):\DateTimeImmutable
    {
        $now=new \DateTimeImmutable('now');
        return match($period){
            'daily'=>$now->setTime(0,0),
            'weekly'=>$now->modify('monday this week')->setTime(0,0),
            default=>$now->modify('first day of this month')->setTime(0,0),
        };
    }
}
