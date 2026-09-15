<?php

namespace Evolvex\AgentFabric\Data;

final readonly class EntityReference
{
    public function __construct(
        public string $type,
        public string|int $id,
        public string|int $tenantId,
        public array $attributes = [],
    ) {}

    public function key(): string { return $this->type.':'.$this->id; }
}
