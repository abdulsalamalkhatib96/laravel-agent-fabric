<?php

namespace Evolvex\AgentFabric\Data;

final readonly class AgentDefinition
{
    public function __construct(
        public string $name,
        public string $version,
        public string $goal,
        public string $instructions,
        public array $tools = [],
        public array $knowledgeSources = [],
        public array $policies = [],
        public array $requiredCapabilities = [],
        public array $metadata = [],
    ) {}
}
