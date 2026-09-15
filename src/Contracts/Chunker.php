<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\KnowledgeChunk;

interface Chunker
{
    /** @return list<KnowledgeChunk> */
    public function chunk(string $content, array $metadata = []): array;
}
