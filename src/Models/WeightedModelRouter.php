<?php

namespace Evolvex\AgentFabric\Models;

use Evolvex\AgentFabric\Contracts\CircuitBreaker;
use Evolvex\AgentFabric\Contracts\ModelRouter;
use Evolvex\AgentFabric\Contracts\ModelTelemetry;
use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelRequest;
use RuntimeException;

final class WeightedModelRouter implements ModelRouter
{
    public function __construct(
        private readonly ConfigModelCatalog $catalog,
        private readonly ModelTelemetry $telemetry,
        private readonly CircuitBreaker $breakers,
    ) {}

    public function route(ModelRequest $request): ModelProfile
    {
        $classification=$request->context->metadata['data_classification']??null;
        $region=$request->context->metadata['required_region']??null;
        $zero=(bool)($request->context->metadata['zero_retention_required']??false);
        $candidates=array_values(array_filter($this->catalog->all(),function(ModelProfile $profile)use($request,$classification,$region,$zero):bool{
            foreach($request->requiredCapabilities as $capability)if(!$profile->supports($capability))return false;
            if($profile->provider===''||$profile->model===''||!$profile->permits($classification,$region,$zero))return false;
            return $this->breakers->allow('model:'.$profile->provider.':'.$profile->model);
        }));
        if($candidates===[])throw new RuntimeException('No healthy configured AI model satisfies capabilities and data-governance requirements.');
        $weights=config('agent-fabric.routing.weights',[]);
        usort($candidates,fn(ModelProfile $a,ModelProfile $b)=>$this->score($b,$weights)<=>$this->score($a,$weights));
        return $candidates[0];
    }

    private function score(ModelProfile $p,array $w):float
    {
        $base=$p->quality*($w['quality']??.35)+$p->toolAccuracy*($w['tool_accuracy']??.20)+$p->reliability*($w['reliability']??.15)+$p->latencyScore*($w['latency']??.10)+$p->costScore*($w['cost']??.10)+$p->historicalEval*($w['historical_eval']??.10);
        $m=$this->telemetry->metrics($p->provider,$p->model);
        if($m===[])return $base;
        $success=isset($m['success_rate'])?(float)$m['success_rate']:.5;
        $p95=isset($m['p95_latency_ms'])?(int)$m['p95_latency_ms']:0;
        $avgCost=isset($m['avg_cost'])?(float)$m['avg_cost']:0;
        $live=($success*.65)+((1/(1+($p95/5000)))*.20)+((1/(1+($avgCost*10)))*.15);
        $liveWeight=(float)config('agent-fabric.routing.live_metrics_weight',.25);
        return ($base*(1-$liveWeight))+($live*$liveWeight);
    }
}
