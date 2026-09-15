<?php

namespace Evolvex\AgentFabric\Contracts;

interface TraceRecorder
{
    public function record(string $traceId, string $span, string $event, array $attributes = [], ?float $durationMs = null): void;
}
