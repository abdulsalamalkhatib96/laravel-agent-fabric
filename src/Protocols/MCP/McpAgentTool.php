<?php

namespace Evolvex\AgentFabric\Protocols\MCP;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolKind;
use Evolvex\AgentFabric\Enums\ToolRisk;
use Evolvex\AgentFabric\Tools\AbstractGovernedTool;

final class McpAgentTool extends AbstractGovernedTool
{
    public function __construct(private readonly McpClientRegistry $clients,private readonly string $serverName,private readonly string $toolName,private readonly string $toolDescription='',private readonly array $schema=['type'=>'object','properties'=>[]]){}
    public function name(): string{return 'mcp.'.$this->serverName.'.'.$this->toolName;}
    public function description(): string{return $this->toolDescription;}
    public function inputSchema(): array{return $this->schema;}
    public function risk(): ToolRisk{return ToolRisk::External;}
    public function kind(): ToolKind{return ToolKind::RemoteOperation;}
    public function execute(AgentContext $context,array $arguments): ToolResult{ $data=$this->clients->get($this->serverName)->callTool($this->toolName,$arguments,['tenant_id'=>$context->tenantId,'actor_id'=>$context->actorId]); return ToolResult::success($data,evidence:['mcp:'.$this->serverName.':'.$this->toolName]); }
}
