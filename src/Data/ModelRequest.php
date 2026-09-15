<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ModelRequest
{
    public function __construct(
        public string $system,
        public string $prompt,
        public AgentContext $context,
        public array $requiredCapabilities = [],
        public array $metadata = [],
        public ?int $timeout = null,
    ) {}
}
