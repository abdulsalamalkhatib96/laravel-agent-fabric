<?php

namespace Evolvex\AgentFabric\Protocols\A2A;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Identity\DelegationService;
use Evolvex\AgentFabric\Runtime\AgentRuntime;

final class LocalA2AGateway
{
    public function __construct(private readonly AgentRegistry $agents,private readonly AgentRuntime $runtime,private readonly DelegationService $delegations){}
    public function card(string $agent,string $endpoint):AgentCard{$definition=$this->agents->get($agent)->definition();return new AgentCard($definition->name,$endpoint,$definition->workflows,$definition->requiredCapabilities,['delegated_identity'=>true,'scoped_tokens'=>true],['version'=>$definition->version]);}
    public function delegate(string $agent,AgentContext $context,string $task):array
    {
        if(config('agent-fabric.protocols.a2a.require_delegation_token',false)){
            $token=$context->metadata['delegation_token']??null;
            if(!is_string($token)||!$this->delegations->validate($token,(string)$context->tenantId,$agent,'agent:'.$agent.':delegate'))throw new \RuntimeException('A2A delegation token is missing, expired, or outside scope.');
        }
        $result=$this->runtime->run($this->agents->get($agent),$context,$task);
        return ['run_id'=>$result->runId,'status'=>$result->status->value,'answer'=>$result->answer,'evidence'=>$result->evidence];
    }
}
