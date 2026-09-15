<?php

namespace Evolvex\AgentFabric\Contracts;

interface McpClient
{
    public function server(): string;
    public function listTools(): array;
    public function callTool(string $name, array $arguments, array $context = []): array;
    public function listResources(): array;
    public function readResource(string $uri): mixed;
}
