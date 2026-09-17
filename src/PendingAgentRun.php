<?php

namespace Evolvex\AgentFabric;

use Evolvex\AgentFabric\Agents\AgentBlueprint;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AgentResult;
use Evolvex\AgentFabric\Runtime\AgentRuntime;
use Illuminate\Support\Str;

final class PendingAgentRun
{
    private string|int|null $tenantId=null; private string|int|null $actorId=null; private ?string $actorType=null; private array $metadata=[]; private ?string $correlationId=null;
    public function __construct(private readonly AgentBlueprint $agent, private readonly AgentRuntime $runtime) {}
    public function tenant(string|int $id): self { $c=clone $this; $c->tenantId=$id; return $c; }
    public function actor(string|int|null $id, ?string $type=null): self { $c=clone $this; $c->actorId=$id; $c->actorType=$type; return $c; }
    public function metadata(array $metadata): self { $c=clone $this; $c->metadata=array_replace($c->metadata,$metadata); return $c; }
    public function simulate(bool $enabled=true): self { return $this->metadata(['execution_mode'=>$enabled?'simulate':'live']); }
    public function dataClassification(string $classification): self { return $this->metadata(['data_classification'=>$classification]); }
    public function region(string $region): self { return $this->metadata(['required_region'=>$region]); }
    public function requireZeroRetention(bool $required=true): self { return $this->metadata(['zero_retention_required'=>$required]); }
    public function role(string $role): self { return $this->metadata(['role'=>$role]); }
    public function department(string $department): self { return $this->metadata(['department'=>$department]); }
    public function clearance(string $clearance): self { return $this->metadata(['clearance'=>$clearance]); }
    public function executionMode(string $mode): self { if(!in_array($mode,['live','simulate'],true))throw new \InvalidArgumentException('Execution mode must be live or simulate.'); return $this->metadata(['execution_mode'=>$mode]); }
    public function correlationId(string $id): self { $c=clone $this; $c->correlationId=$id; return $c; }
    public function ask(string $input): AgentResult { return $this->runtime->run($this->agent,$this->context(),$input); }
    public function resume(string $runId,string $approvalId): AgentResult { return $this->runtime->resume($this->agent,$this->context(),$runId,$approvalId); }
    public function continue(string $runId,string $message): AgentResult { return $this->runtime->continueWithUser($this->agent,$this->context(),$runId,$message); }
    private function context(): AgentContext
    {
        if(config('agent-fabric.security.require_tenant',true)&&$this->tenantId===null)throw new \InvalidArgumentException('A tenant id is required.');
        return new AgentContext($this->tenantId??'global',$this->actorId,$this->actorType,$this->metadata,$this->correlationId??(string)Str::uuid());
    }
}
