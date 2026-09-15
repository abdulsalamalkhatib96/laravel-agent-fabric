<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\KnowledgeStore;
use Evolvex\AgentFabric\Data\KnowledgeDocument;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseKnowledgeStore implements KnowledgeStore
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function hashFor(string $sourceType, string|int $tenantId, string $sourceKey): ?string
    {
        $value=$this->db->table('ai_knowledge_documents')->where('tenant_id',(string)$tenantId)->where('source_type',$sourceType)->where('source_key',$sourceKey)->value('content_hash');
        return $value===null?null:(string)$value;
    }

    public function upsertDocument(KnowledgeDocument $document, array $chunks): string
    {
        return $this->db->transaction(function () use ($document, $chunks): string {
            $identity=['tenant_id'=>(string)$document->tenantId,'source_type'=>$document->sourceType,'source_key'=>$document->sourceKey];
            $candidateId=(string)Str::uuid();
            $this->db->table('ai_knowledge_documents')->insertOrIgnore(['id'=>$candidateId,'title'=>$document->title,'content'=>$document->content,'content_hash'=>$document->contentHash(),'metadata'=>json_encode($document->metadata),'security_level'=>$document->securityLevel,'source_updated_at'=>$document->sourceUpdatedAt,'indexed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]+$identity);
            $id=(string)$this->db->table('ai_knowledge_documents')->where($identity)->value('id');
            $payload=['title'=>$document->title,'content'=>$document->content,'content_hash'=>$document->contentHash(),'metadata'=>json_encode($document->metadata),'security_level'=>$document->securityLevel,'source_updated_at'=>$document->sourceUpdatedAt,'indexed_at'=>now(),'updated_at'=>now()];
            $this->db->table('ai_knowledge_documents')->where('id',$id)->update($payload);
            $this->db->table('ai_knowledge_chunks')->where('document_id', $id)->delete();
            foreach ($chunks as $chunk) {
                $this->db->table('ai_knowledge_chunks')->insert([
                    'id' => (string) Str::uuid(), 'document_id' => $id, 'tenant_id' => (string) $document->tenantId,
                    'chunk_number' => $chunk->number, 'content' => $chunk->content,
                    'embedding' => $chunk->embedding ? json_encode($chunk->embedding) : null,
                    'metadata' => json_encode($chunk->metadata), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $id;
        });
    }

    public function deleteMissing(string $sourceType, string|int $tenantId, array $seenKeys): int
    {
        $q = $this->db->table('ai_knowledge_documents')->where('tenant_id', (string) $tenantId)->where('source_type', $sourceType);
        if ($seenKeys !== []) $q->whereNotIn('source_key', $seenKeys);
        $ids = $q->pluck('id')->all();
        if ($ids === []) return 0;
        return $this->db->transaction(function () use ($ids): int {
            $this->db->table('ai_knowledge_chunks')->whereIn('document_id', $ids)->delete();
            return $this->db->table('ai_knowledge_documents')->whereIn('id', $ids)->delete();
        });
    }
}
