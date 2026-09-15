<?php

namespace Evolvex\AgentFabric\Memory;

use Evolvex\AgentFabric\Contracts\MemoryStore;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Enums\MemoryKind;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseMemoryStore implements MemoryStore
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function remember(AgentContext $context, string $agent, MemoryKind $kind, string $key, mixed $value, float $confidence = 1.0, ?\DateTimeInterface $expiresAt = null): void
    {
        $identity=['tenant_id'=>(string)$context->tenantId,'actor_type'=>$context->actorType??'','actor_id'=>$context->actorId===null?'':(string)$context->actorId,'agent'=>$agent,'kind'=>$kind->value,'memory_key'=>$key];
        $id=(string)Str::uuid();
        $this->db->table('ai_memories')->insertOrIgnore(['id'=>$id,'value'=>json_encode($value),'confidence'=>$confidence,'expires_at'=>$expiresAt,'created_at'=>now(),'updated_at'=>now()]+$identity);
        $this->db->table('ai_memories')->where($identity)->update(['value'=>json_encode($value),'confidence'=>$confidence,'expires_at'=>$expiresAt,'updated_at'=>now()]);
    }

    public function recall(AgentContext $context, string $agent, array $kinds = []): array
    {
        $q = $this->db->table('ai_memories')->where('tenant_id',(string)$context->tenantId)->where('agent',$agent)
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at','>',now()); });
        if ($context->actorId !== null) $q->where('actor_id',(string)$context->actorId); else $q->where('actor_id','');
        if ($kinds !== []) $q->whereIn('kind', array_map(fn ($k) => $k instanceof MemoryKind ? $k->value : $k, $kinds));
        return $q->orderByDesc('confidence')->limit(50)->get()->map(fn ($r) => [
            'kind'=>$r->kind,'key'=>$r->memory_key,'value'=>json_decode($r->value,true),'confidence'=>(float)$r->confidence
        ])->all();
    }
}
