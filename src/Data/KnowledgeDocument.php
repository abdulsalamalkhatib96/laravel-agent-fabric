<?php

namespace Evolvex\AgentFabric\Data;

final readonly class KnowledgeDocument
{
    public function __construct(
        public string $sourceType,
        public string $sourceKey,
        public string $title,
        public string $content,
        public string|int $tenantId,
        public array $metadata = [],
        public string $securityLevel = 'internal',
        public ?string $sourceUpdatedAt = null,
    ) {}

    public function contentHash(): string { return hash('sha256', $this->content); }
}
