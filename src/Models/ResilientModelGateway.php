<?php

namespace Evolvex\AgentFabric\Models;

use Evolvex\AgentFabric\Contracts\CircuitBreaker;
use Evolvex\AgentFabric\Contracts\ModelGateway;
use Evolvex\AgentFabric\Contracts\ModelTelemetry;
use Evolvex\AgentFabric\Contracts\QuotaManager;
use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelRequest;
use Evolvex\AgentFabric\Data\ModelResponse;
use Throwable;

final class ResilientModelGateway implements ModelGateway
{
    public function __construct(
        private readonly LaravelAiGateway $inner,
        private readonly ConfigModelCatalog $catalog,
        private readonly CircuitBreaker $breakers,
        private readonly ModelTelemetry $telemetry,
        private readonly ModelFailureClassifier $classifier,
        private readonly QuotaManager $quotas,
    ) {}

    public function generate(ModelRequest $request, ModelProfile $profile): ModelResponse
    {
        $candidates=$this->candidates($request,$profile);
        $last=null;
        foreach($candidates as $candidate){
            $breaker='model:'.$candidate->provider.':'.$candidate->model;
            if(! $this->breakers->allow($breaker))continue;
            $quotaKey=(string)$request->context->tenantId.':'.$candidate->provider.':'.$candidate->model;
            if(! $this->quotas->consume('model_calls',$quotaKey,1))continue;
            $started=microtime(true);
            try{
                $response=$this->inner->generate($request,$candidate);
                $latency=(microtime(true)-$started)*1000;
                $this->breakers->success($breaker);
                $this->telemetry->record($candidate,$latency,true,$response);
                if($candidate->key!==$profile->key){
                    return new ModelResponse($response->text,$response->provider,$response->model,$response->inputTokens,$response->outputTokens,$response->cost,array_replace($response->metadata,['failover_from'=>$profile->key,'failover_to'=>$candidate->key]));
                }
                return $response;
            }catch(Throwable $e){
                $last=$e;$class=$this->classifier->classify($e);$latency=(microtime(true)-$started)*1000;
                $this->telemetry->record($candidate,$latency,false,null,$class);
                $this->breakers->failure($breaker,$class.': '.$e->getMessage());
                if(!$this->classifier->retryable($class))throw $e;
            }
        }
        if($last)throw $last;
        throw new \RuntimeException('No healthy model candidate is available.');
    }

    private function candidates(ModelRequest $request,ModelProfile $primary):array
    {
        $classification=$request->context->metadata['data_classification']??null;
        $region=$request->context->metadata['required_region']??null;
        $zero=(bool)($request->context->metadata['zero_retention_required']??false);
        $all=array_filter($this->catalog->all(),function(ModelProfile $p)use($request,$classification,$region,$zero){
            foreach($request->requiredCapabilities as $capability)if(!$p->supports($capability))return false;
            return $p->provider!==''&&$p->model!==''&&$p->permits($classification,$region,$zero);
        });
        $rest=array_values(array_filter($all,fn(ModelProfile $p)=>$p->key!==$primary->key));
        usort($rest,fn(ModelProfile $a,ModelProfile $b)=>(($b->contextWindow??0)<=>($a->contextWindow??0)));
        $max=max(1,(int)config('agent-fabric.routing.failover.max_candidates',3));
        return array_slice(array_merge([$primary],$rest),0,$max);
    }
}
