<?php

namespace Evolvex\AgentFabric\Observability;

use Evolvex\AgentFabric\Contracts\TelemetryExporter;

final class OpenTelemetryExporter implements TelemetryExporter
{
    public function export(string $traceId,string $span,string $event,array $attributes=[],?float $durationMs=null):void
    {
        if(!class_exists('OpenTelemetry\\API\\Globals'))return;
        try{
            $provider=\OpenTelemetry\API\Globals::tracerProvider();
            $tracer=$provider->getTracer('evolvex/laravel-agent-fabric');
            $builder=$tracer->spanBuilder($span.'.'.$event);
            $otelSpan=$builder->startSpan();
            $otelSpan->setAttribute('agent_fabric.trace_id',$traceId);
            foreach($attributes as $key=>$value){if(is_scalar($value)||$value===null)$otelSpan->setAttribute('agent_fabric.'.str_replace(' ','_',strtolower((string)$key)),$value);}
            if($durationMs!==null)$otelSpan->setAttribute('agent_fabric.duration_ms',$durationMs);
            $otelSpan->end();
        }catch(\Throwable){}
    }
}
