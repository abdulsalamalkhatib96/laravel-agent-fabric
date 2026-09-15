<?php

namespace Evolvex\AgentFabric\Data;

final readonly class KnowledgeChunk
{
    public function __construct(
        public string $content,
        public int $number,
        public array $metadata = [],
        public ?array $embedding = null,
    ) {}
}
