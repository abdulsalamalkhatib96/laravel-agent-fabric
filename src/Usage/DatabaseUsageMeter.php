<?php

namespace Evolvex\AgentFabric\Usage;

use Evolvex\AgentFabric\Contracts\UsageMeter;
use Evolvex\AgentFabric\Data\ModelResponse;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseUsageMeter implements UsageMeter
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function record(string $runId,ModelResponse $response):void
    {
        $cost=(float)($response->cost??0);
        $this->db->transaction(function()use($runId,$response,$cost):void{
            $this->db->table('ai_usage')->insert(['id'=>(string)Str::uuid(),'run_id'=>$runId,'provider'=>$response->provider,'model'=>$response->model,'input_tokens'=>$response->inputTokens,'output_tokens'=>$response->outputTokens,'cost'=>$cost,'metadata'=>json_encode($response->metadata),'created_at'=>now(),'updated_at'=>now()]);
            if($cost<=0)return;
            $run=$this->db->table('ai_runs')->where('id',$runId)->first();if(!$run)return;
            $this->incrementBudget((string)$run->tenant_id,'tenant',(string)$run->tenant_id,$cost);
            $this->incrementBudget((string)$run->tenant_id,'agent',(string)$run->agent,$cost);
        });
    }

    public function cost(string $runId):float{return (float)$this->db->table('ai_usage')->where('run_id',$runId)->sum('cost');}

    private function incrementBudget(string $tenantId,string $scope,string $scopeKey,float $amount):void
    {
        $this->db->table('ai_cost_budgets')->where('tenant_id',$tenantId)->where('scope',$scope)->where('scope_key',$scopeKey)->update(['spent'=>$this->db->raw('spent + '.sprintf('%.8F',$amount)),'updated_at'=>now()]);
    }
}
