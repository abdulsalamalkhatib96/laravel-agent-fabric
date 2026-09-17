<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelResponse;

interface ModelTelemetry
{
    public function record(ModelProfile $profile, float $latencyMs, bool $success, ?ModelResponse $response = null, ?string $failureClass = null): void;
    public function metrics(string $provider, string $model): array;
}
