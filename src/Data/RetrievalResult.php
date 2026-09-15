<?php

namespace Evolvex\AgentFabric\Data;

final readonly class RetrievalResult
{
    public function __construct(
        public string $content,
        public float $score,
        public string $documentId,
        public string $chunkId,
        public array $metadata = [],
    ) {}
}
