<?php

namespace Evolvex\AgentFabric\Data;

final readonly class KnowledgeProvenance
{
    public function __construct(
        public string $source,
        public ?string $sourceId = null,
        public float $authority = 0.5,
        public ?\DateTimeImmutable $effectiveFrom = null,
        public ?\DateTimeImmutable $effectiveUntil = null,
        public ?\DateTimeImmutable $observedAt = null,
        public array $metadata = [],
    ) {}
}
