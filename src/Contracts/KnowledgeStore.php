<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\KnowledgeChunk;
use Evolvex\AgentFabric\Data\KnowledgeDocument;

interface KnowledgeStore
{
    public function hashFor(string $sourceType, string|int $tenantId, string $sourceKey): ?string;
    public function upsertDocument(KnowledgeDocument $document, array $chunks): string;
    public function deleteMissing(string $sourceType, string|int $tenantId, array $seenKeys): int;
}
