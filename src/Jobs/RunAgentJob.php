<?php

namespace Evolvex\AgentFabric\Jobs;

use Evolvex\AgentFabric\AgentFabricManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RunAgentJob implements ShouldQueue
{
    use InteractsWithQueue,Queueable,SerializesModels;
    public int $tries=3; public int $timeout=300;
    public function __construct(public string $agent,public string|int $tenantId,public string $input,public string|int|null $actorId=null,public ?string $actorType=null,public array $metadata=[]) { $this->onQueue(config('agent-fabric.queues.interactive','ai-interactive')); }
    public function handle(AgentFabricManager $fabric): void { $fabric->agent($this->agent)->tenant($this->tenantId)->actor($this->actorId,$this->actorType)->metadata($this->metadata)->ask($this->input); }
}
