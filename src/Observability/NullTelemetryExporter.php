<?php

namespace Evolvex\AgentFabric\Observability;

use Evolvex\AgentFabric\Contracts\TelemetryExporter;

final class NullTelemetryExporter implements TelemetryExporter
{
    public function export(string $traceId,string $span,string $event,array $attributes=[],?float $durationMs=null):void{}
}
