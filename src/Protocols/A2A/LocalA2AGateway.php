<?php

namespace Evolvex\AgentFabric\Protocols\A2A;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Runtime\AgentRuntime;

final class LocalA2AGateway
{
    public function __construct(private readonly AgentRegistry $agents, private readonly AgentRuntime $runtime) {}

    public function card(string $agent, string $endpoint): AgentCard
    {
        $definition=$this->agents->get($agent)->definition();
        return new AgentCard($definition->name,$endpoint,$definition->workflows,$definition->requiredCapabilities,['delegated_identity'=>true],['version'=>$definition->version]);
    }

    public function delegate(string $agent, AgentContext $context, string $task): array
    {
        $result=$this->runtime->run($this->agents->get($agent),$context,$task);
        return ['run_id'=>$result->runId,'status'=>$result->status->value,'answer'=>$result->answer,'evidence'=>$result->evidence];
    }
}
