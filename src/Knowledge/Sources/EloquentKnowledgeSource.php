<?php

namespace Evolvex\AgentFabric\Knowledge\Sources;

use Evolvex\AgentFabric\Contracts\KnowledgeSource;
use Evolvex\AgentFabric\Contracts\TenantAwareKnowledgeSource;
use Evolvex\AgentFabric\Data\KnowledgeDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class EloquentKnowledgeSource implements KnowledgeSource, TenantAwareKnowledgeSource
{
    public function __construct(
        private readonly string $sourceName,
        private readonly string $modelClass,
        private readonly string $tenantColumn,
        private readonly array $contentFields,
        private readonly array $metadataFields = [],
        private readonly ?string $titleField = null,
        private readonly ?string $tenantId = null,
    ) {}

    public function name(): string { return $this->sourceName; }
    public function tenantIds(): iterable { if($this->tenantId!==null) yield $this->tenantId; }

    public function documents(): iterable
    {
        /** @var Model $model */ $model = new $this->modelClass;
        $query = $model->newQuery();
        if ($this->tenantId !== null) $query->where($this->tenantColumn, $this->tenantId);
        foreach ($query->cursor() as $row) {
            $parts = []; foreach ($this->contentFields as $f) $parts[] = $f.': '.(string) data_get($row, $f);
            $metadata = []; foreach ($this->metadataFields as $f) $metadata[$f] = data_get($row, $f);
            yield new KnowledgeDocument(
                sourceType: $this->sourceName, sourceKey: (string) $row->getKey(),
                title: $this->titleField ? (string) data_get($row, $this->titleField) : $this->sourceName.' '.$row->getKey(),
                content: implode("\n", $parts), tenantId: (string) data_get($row, $this->tenantColumn), metadata: $metadata,
                sourceUpdatedAt: method_exists($row, 'getUpdatedAtColumn') ? optional($row->{$row->getUpdatedAtColumn()})->toISOString() : null,
            );
        }
    }
}
