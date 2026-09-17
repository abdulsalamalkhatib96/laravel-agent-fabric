<?php

namespace Evolvex\AgentFabric\Contracts;

interface TelemetryExporter
{
    public function export(string $traceId, string $span, string $event, array $attributes = [], ?float $durationMs = null): void;
}
