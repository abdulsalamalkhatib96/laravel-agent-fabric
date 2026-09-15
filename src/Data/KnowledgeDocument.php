<?php

namespace Evolvex\AgentFabric\Data;

final readonly class KnowledgeDocument
{
    /** @param list<EntityReference> $entities */
    public function __construct(
        public string $sourceType,
        public string $sourceKey,
        public string $title,
        public string $content,
        public string|int $tenantId,
        public array $metadata = [],
        public string $securityLevel = 'internal',
        public ?string $sourceUpdatedAt = null,
        public ?KnowledgeProvenance $provenance = null,
        public array $entities = [],
    ) {}

    public function contentHash(): string { return hash('sha256', $this->content); }
    public function enrichedMetadata(): array
    {
        $metadata=$this->metadata;
        if($this->provenance)$metadata['_provenance']=[
            'source'=>$this->provenance->source,'source_id'=>$this->provenance->sourceId,'authority'=>$this->provenance->authority,
            'effective_from'=>$this->provenance->effectiveFrom?->format(DATE_ATOM),'effective_until'=>$this->provenance->effectiveUntil?->format(DATE_ATOM),
            'observed_at'=>$this->provenance->observedAt?->format(DATE_ATOM),'metadata'=>$this->provenance->metadata,
        ];
        if($this->entities!==[])$metadata['_entities']=array_map(fn(EntityReference $e)=>['type'=>$e->type,'id'=>$e->id,'key'=>$e->key()],$this->entities);
        return $metadata;
    }
}
