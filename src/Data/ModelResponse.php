<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ModelResponse
{
    public function __construct(
        public string $text,
        public ?string $provider = null,
        public ?string $model = null,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?float $cost = null,
        public array $metadata = [],
    ) {}
}
