<?php

namespace Evolvex\AgentFabric\Observability;

use Evolvex\AgentFabric\Contracts\ModelTelemetry;
use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelResponse;
use Illuminate\Database\ConnectionInterface;

final class DatabaseModelTelemetry implements ModelTelemetry
{
    public function __construct(private readonly ConnectionInterface $db) {}
    public function record(ModelProfile $profile,float $latencyMs,bool $success,?ModelResponse $response=null,?string $failureClass=null):void
    {
        $this->db->table('ai_model_observations')->insert(['provider'=>$profile->provider,'model'=>$profile->model,'success'=>$success,'latency_ms'=>$latencyMs,'input_tokens'=>$response?->inputTokens,'output_tokens'=>$response?->outputTokens,'cost'=>$response?->cost??0,'failure_class'=>$failureClass,'created_at'=>now(),'updated_at'=>now()]);
        $rows=$this->db->table('ai_model_observations')->where('provider',$profile->provider)->where('model',$profile->model)->orderByDesc('id')->limit(200)->get();
        if($rows->isEmpty())return;
        $latencies=$rows->pluck('latency_ms')->filter()->sort()->values();$idx=max(0,(int)ceil($latencies->count()*.95)-1);
        $this->db->table('ai_model_metrics')->updateOrInsert(['provider'=>$profile->provider,'model'=>$profile->model],[
            'success_rate'=>$rows->where('success',1)->count()/$rows->count(),'p95_latency_ms'=>(int)($latencies[$idx]??0),'avg_cost'=>(float)$rows->avg('cost'),'updated_at'=>now(),'created_at'=>now(),
        ]);
    }
    public function metrics(string $provider,string $model):array{return (array)($this->db->table('ai_model_metrics')->where(compact('provider','model'))->first()??[]);}
}
