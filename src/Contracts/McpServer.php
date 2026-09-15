<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;

interface McpServer
{
    public function listTools(): array;
    public function callTool(string $runId, AgentContext $context, string $name, array $arguments): array;
}
