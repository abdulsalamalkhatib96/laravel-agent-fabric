<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\ModelResponse;

interface UsageMeter
{
    public function record(string $runId, ModelResponse $response): void;
    public function cost(string $runId): float;
}
