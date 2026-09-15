<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ConnectorSchema
{
    public function __construct(
        public string $name,
        public array $resources = [],
        public array $actions = [],
        public array $capabilities = [],
        public array $metadata = [],
    ) {}
}
