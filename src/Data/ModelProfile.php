<?php

namespace Evolvex\AgentFabric\Data;

use Evolvex\AgentFabric\Enums\ModelCapability;

final readonly class ModelProfile
{
    public function __construct(
        public string $key,
        public string $provider,
        public string $model,
        public array $capabilities = [],
        public float $quality = 0.5,
        public float $toolAccuracy = 0.5,
        public float $reliability = 0.5,
        public float $latencyScore = 0.5,
        public float $costScore = 0.5,
        public float $historicalEval = 0.5,
        public ?int $contextWindow = null,
        public array $metadata = [],
    ) {}

    public function supports(ModelCapability|string $capability): bool
    {
        $value = $capability instanceof ModelCapability ? $capability->value : $capability;
        return in_array($value, array_map(fn ($c) => $c instanceof ModelCapability ? $c->value : $c, $this->capabilities), true);
    }
}
