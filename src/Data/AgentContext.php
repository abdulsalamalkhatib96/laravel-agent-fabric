<?php

namespace Evolvex\AgentFabric\Data;

final readonly class AgentContext
{
    public function __construct(
        public string|int $tenantId,
        public string|int|null $actorId = null,
        public ?string $actorType = null,
        public array $metadata = [],
        public ?string $correlationId = null,
    ) {}

    public function with(array $metadata): self
    {
        return new self($this->tenantId, $this->actorId, $this->actorType, array_replace($this->metadata, $metadata), $this->correlationId);
    }
}
