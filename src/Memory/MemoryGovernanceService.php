<?php

namespace Evolvex\AgentFabric\Memory;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Enums\MemoryKind;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class MemoryGovernanceService
{
    public function __construct(private readonly ConnectionInterface $db, private readonly DatabaseMemoryStore $memory) {}

    public function propose(AgentContext $context, string $agent, string $key, mixed $value, float $confidence = .5, ?\DateTimeInterface $expiresAt = null): string
    {
        $id=(string)Str::uuid();
        $this->db->table('ai_memory_candidates')->insert([
            'id'=>$id,'tenant_id'=>(string)$context->tenantId,'actor_type'=>$context->actorType??'','actor_id'=>$context->actorId===null?'':(string)$context->actorId,
            'agent'=>$agent,'memory_key'=>$key,'value'=>json_encode($value),'confidence'=>max(0,min(1,$confidence)),'status'=>'candidate','expires_at'=>$expiresAt,'created_at'=>now(),'updated_at'=>now(),
        ]);
        return $id;
    }

    public function approve(string $id, string $decidedBy): void
    {
        $row=$this->db->table('ai_memory_candidates')->where('id',$id)->where('status','candidate')->first();
        if(!$row)throw new \InvalidArgumentException('Unknown or already decided memory candidate.');
        $context=new AgentContext($row->tenant_id,$row->actor_id===''?null:$row->actor_id,$row->actor_type===''?null:$row->actor_type);
        $this->memory->remember($context,(string)$row->agent,MemoryKind::Semantic,(string)$row->memory_key,json_decode((string)$row->value,true),(float)$row->confidence,$row->expires_at?new \DateTimeImmutable((string)$row->expires_at):null);
        $this->db->table('ai_memory_candidates')->where('id',$id)->update(['status'=>'approved','decided_by'=>$decidedBy,'decided_at'=>now(),'updated_at'=>now()]);
    }

    public function reject(string $id, string $decidedBy): void
    {
        $updated=$this->db->table('ai_memory_candidates')->where('id',$id)->where('status','candidate')->update(['status'=>'rejected','decided_by'=>$decidedBy,'decided_at'=>now(),'updated_at'=>now()]);
        if($updated!==1)throw new \InvalidArgumentException('Unknown or already decided memory candidate.');
    }
}
