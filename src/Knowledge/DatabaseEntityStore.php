<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\EntityStore;
use Evolvex\AgentFabric\Data\EntityReference;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseEntityStore implements EntityStore
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function put(EntityReference $entity): void
    {
        $identity = ['tenant_id'=>(string)$entity->tenantId,'entity_type'=>$entity->type,'entity_id'=>(string)$entity->id];
        $existing = $this->db->table('ai_entities')->where($identity)->first();
        if ($existing) {
            $this->db->table('ai_entities')->where('id', $existing->id)->update(['attributes'=>json_encode($entity->attributes),'updated_at'=>now()]);
            return;
        }
        $this->db->table('ai_entities')->insert($identity + ['id'=>(string)Str::uuid(),'attributes'=>json_encode($entity->attributes),'created_at'=>now(),'updated_at'=>now()]);
    }

    public function relate(EntityReference $from, string $relation, EntityReference $to, array $metadata = []): void
    {
        $identity = ['tenant_id'=>(string)$from->tenantId,'from_key'=>$from->key(),'relation'=>$relation,'to_key'=>$to->key()];
        $existing = $this->db->table('ai_entity_relations')->where($identity)->first();
        if ($existing) {
            $this->db->table('ai_entity_relations')->where('id',$existing->id)->update(['metadata'=>json_encode($metadata),'updated_at'=>now()]);
            return;
        }
        $this->db->table('ai_entity_relations')->insert($identity + ['id'=>(string)Str::uuid(),'metadata'=>json_encode($metadata),'created_at'=>now(),'updated_at'=>now()]);
    }

    public function get(string|int $tenantId, string $type, string|int $id): ?EntityReference
    {
        $row=$this->db->table('ai_entities')->where(['tenant_id'=>(string)$tenantId,'entity_type'=>$type,'entity_id'=>(string)$id])->first();
        return $row ? new EntityReference($type,$id,$tenantId,json_decode((string)$row->attributes,true)?:[]) : null;
    }

    public function related(EntityReference $entity, ?string $relation = null): array
    {
        $query=$this->db->table('ai_entity_relations')->where('tenant_id',(string)$entity->tenantId)->where('from_key',$entity->key());
        if($relation)$query->where('relation',$relation);
        return $query->get()->map(fn($r)=>['relation'=>$r->relation,'to'=>$r->to_key,'metadata'=>json_decode((string)$r->metadata,true)?:[]])->all();
    }
}
