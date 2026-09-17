<?php

namespace Evolvex\AgentFabric\Observability;

use Evolvex\AgentFabric\Contracts\TelemetryExporter;

final class CompositeTelemetryExporter implements TelemetryExporter
{
    public function __construct(private array $exporters = []) {}
    public function add(TelemetryExporter $exporter): self { $this->exporters[]=$exporter; return $this; }
    public function export(string $traceId,string $span,string $event,array $attributes=[],?float $durationMs=null):void{foreach($this->exporters as $exporter){try{$exporter->export($traceId,$span,$event,$attributes,$durationMs);}catch(\Throwable){}}}
}
