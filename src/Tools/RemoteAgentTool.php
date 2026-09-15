<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Contracts\AgentTool;
use Evolvex\AgentFabric\Contracts\RemoteToolClient;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolRisk;

final class RemoteAgentTool implements AgentTool
{
    public function __construct(private readonly RemoteToolClient $client,private readonly string $server,private readonly string $toolName,private readonly string $toolDescription,private readonly array $schema,private readonly ToolRisk $toolRisk=ToolRisk::External){}
    public function name(): string{return $this->toolName;} public function description(): string{return $this->toolDescription;} public function inputSchema(): array{return $this->schema;} public function risk(): ToolRisk{return $this->toolRisk;}
    public function execute(AgentContext $context,array $arguments): ToolResult{try{$data=$this->client->call($this->server,$this->toolName,$arguments,['tenant_id'=>$context->tenantId,'actor_id'=>$context->actorId,'correlation_id'=>$context->correlationId]);return ToolResult::success($data,null,["remote:{$this->server}:{$this->toolName}"]);}catch(\Throwable $e){return ToolResult::failure($e->getMessage());}}
}
