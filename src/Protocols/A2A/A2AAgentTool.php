<?php

namespace Evolvex\AgentFabric\Protocols\A2A;

use Evolvex\AgentFabric\Contracts\A2AClient;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolKind;
use Evolvex\AgentFabric\Enums\ToolRisk;
use Evolvex\AgentFabric\Identity\DelegationService;
use Evolvex\AgentFabric\Tools\AbstractGovernedTool;

final class A2AAgentTool extends AbstractGovernedTool
{
    public function __construct(
        private readonly A2AClient $client,
        private readonly string $endpoint,
        private readonly string $remoteAgentName,
        private readonly ?DelegationService $delegations=null,
        private readonly string $sourceAgentName='local',
    ){}
    public function name():string{return 'a2a.'.$this->remoteAgentName;}
    public function description():string{return 'Delegate a bounded task to remote agent '.$this->remoteAgentName;}
    public function inputSchema():array{return ['type'=>'object','properties'=>['task'=>['type'=>'string','minLength'=>1],'context'=>['type'=>'object']],'required'=>['task'],'additionalProperties'=>false];}
    public function risk():ToolRisk{return ToolRisk::External;}
    public function kind():ToolKind{return ToolKind::RemoteOperation;}
    public function sideEffects():array{return ['remote_agent_delegation'];}
    public function execute(AgentContext $context,array $arguments):ToolResult
    {
        $delegation=$this->delegations?->issue((string)$context->tenantId,$this->sourceAgentName,$this->remoteAgentName,['agent:'.$this->remoteAgentName.':delegate'],(int)config('agent-fabric.protocols.a2a.delegation_ttl_seconds',300));
        $remoteContext=array_replace($arguments['context']??[],['tenant_id'=>$context->tenantId,'actor_id'=>$context->actorId,'correlation_id'=>$context->correlationId]);
        if($delegation!==null)$remoteContext['delegation_token']=$delegation;
        $data=$this->client->delegate($this->endpoint,(string)$arguments['task'],$remoteContext);
        return ToolResult::success($data,evidence:['a2a:'.$this->remoteAgentName]);
    }
}
