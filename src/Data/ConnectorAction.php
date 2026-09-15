<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ConnectorAction
{
    public function __construct(
        public string $action,
        public array $arguments = [],
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}
}
