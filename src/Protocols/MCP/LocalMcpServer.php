<?php

namespace Evolvex\AgentFabric\Protocols\MCP;

use Evolvex\AgentFabric\Contracts\McpServer;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Tools\ToolExecutor;
use Evolvex\AgentFabric\Tools\ToolRegistry;

final class LocalMcpServer implements McpServer
{
    public function __construct(private readonly ToolRegistry $tools, private readonly ToolExecutor $executor) {}

    public function listTools(): array
    {
        return array_map(fn ($tool) => [
            'name'=>$tool->name(),'description'=>$tool->description(),'inputSchema'=>$tool->inputSchema(),'risk'=>$tool->risk()->value,
        ], $this->tools->all());
    }

    public function callTool(string $runId, AgentContext $context, string $name, array $arguments): array
    {
        $result=$this->executor->execute($runId,$this->tools,$name,$context,$arguments);
        return ['success'=>$result->success,'data'=>$result->data,'message'=>$result->message,'ambiguous'=>$result->ambiguous,'evidence'=>$result->evidence];
    }
}
