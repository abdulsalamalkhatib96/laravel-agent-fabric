<?php

namespace Evolvex\AgentFabric\Evaluation;

use Evolvex\AgentFabric\AgentFabricManager;
use Evolvex\AgentFabric\Contracts\RunRepository;

final class EvalRunner
{
    public function __construct(private readonly AgentFabricManager $fabric,private readonly RunRepository $runs){}
    /** @return list<EvalResult> */
    public function run(string $agent,array $cases):array
    {
        $out=[];foreach($cases as $case){if(!$case instanceof EvalCase)continue;$started=microtime(true);$result=$this->fabric->agent($agent)->tenant($case->tenantId)->metadata(['evaluation'=>true]+$case->metadata)->ask($case->input);$checks=[];
            foreach($case->expectations as $type=>$expected){$checks[$type]=match($type){'status'=>$result->status->value===$expected,'answer_contains'=>str_contains(mb_strtolower($result->answer??''),mb_strtolower((string)$expected)),'tool_used'=>$this->toolUsed($result->runId,(string)$expected),default=>true};}
            $passed=!in_array(false,$checks,true);$score=$checks===[]?1.0:count(array_filter($checks))/count($checks);$out[]=new EvalResult($case->name,$passed,$score,$result->answer,['checks'=>$checks,'run_id'=>$result->runId,'latency_ms'=>(int)((microtime(true)-$started)*1000)]);
        }return $out;
    }
    private function toolUsed(string $runId,string $tool):bool{foreach($this->runs->steps($runId) as $s){if(($s['type']??'')!=='tool_result')continue;$i=json_decode((string)($s['input']??'{}'),true)?:[];if(($i['tool']??null)===$tool)return true;}return false;}
}
