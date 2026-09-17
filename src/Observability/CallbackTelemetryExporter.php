<?php

namespace Evolvex\AgentFabric\Observability;

use Evolvex\AgentFabric\Contracts\TelemetryExporter;

final class CallbackTelemetryExporter implements TelemetryExporter
{
    public function __construct(private readonly \Closure $callback) {}
    public function export(string $traceId,string $span,string $event,array $attributes=[],?float $durationMs=null):void{($this->callback)($traceId,$span,$event,$attributes,$durationMs);}
}
