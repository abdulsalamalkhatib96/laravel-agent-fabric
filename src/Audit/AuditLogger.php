<?php

namespace Evolvex\AgentFabric\Audit;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Security\SecretRedactor;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class AuditLogger
{
    public function __construct(private readonly ConnectionInterface $db,private readonly SecretRedactor $redactor){}
    public function log(AgentContext $context,string $event,array $payload=[],?string $runId=null):void{$this->db->table('ai_audit_logs')->insert(['id'=>(string)Str::uuid(),'tenant_id'=>(string)$context->tenantId,'run_id'=>$runId,'event'=>$event,'actor_type'=>$context->actorType,'actor_id'=>$context->actorId===null?null:(string)$context->actorId,'payload'=>json_encode($this->redactor->redact($payload)),'correlation_id'=>$context->correlationId,'created_at'=>now(),'updated_at'=>now()]);}
}
