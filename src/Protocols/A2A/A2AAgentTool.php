<?php

namespace Evolvex\AgentFabric\Protocols\A2A;

use Evolvex\AgentFabric\Contracts\A2AClient;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolKind;
use Evolvex\AgentFabric\Enums\ToolRisk;
use Evolvex\AgentFabric\Tools\AbstractGovernedTool;

final class A2AAgentTool extends AbstractGovernedTool
{
    public function __construct(private readonly A2AClient $client,private readonly string $endpoint,private readonly string $remoteAgentName){}
    public function name(): string{return 'a2a.'.$this->remoteAgentName;}
    public function description(): string{return 'Delegate a task to remote agent '.$this->remoteAgentName;}
    public function inputSchema(): array{return ['type'=>'object','properties'=>['task'=>['type'=>'string'],'context'=>['type'=>'object']],'required'=>['task']];}
    public function risk(): ToolRisk{return ToolRisk::External;}
    public function kind(): ToolKind{return ToolKind::RemoteOperation;}
    public function execute(AgentContext $context,array $arguments): ToolResult{ $data=$this->client->delegate($this->endpoint,(string)$arguments['task'],array_replace($arguments['context']??[],['tenant_id'=>$context->tenantId,'actor_id'=>$context->actorId])); return ToolResult::success($data,evidence:['a2a:'.$this->remoteAgentName]); }
}
