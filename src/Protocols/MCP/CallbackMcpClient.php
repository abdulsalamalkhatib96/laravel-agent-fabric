<?php

namespace Evolvex\AgentFabric\Protocols\MCP;

use Closure;
use Evolvex\AgentFabric\Contracts\McpClient;

final class CallbackMcpClient implements McpClient
{
    public function __construct(private readonly string $serverName, private readonly Closure $tools, private readonly Closure $call, private readonly ?Closure $resources=null, private readonly ?Closure $read=null) {}
    public function server(): string { return $this->serverName; }
    public function listTools(): array { return ($this->tools)(); }
    public function callTool(string $name,array $arguments,array $context=[]): array { return ($this->call)($name,$arguments,$context); }
    public function listResources(): array { return $this->resources ? ($this->resources)() : []; }
    public function readResource(string $uri): mixed { return $this->read ? ($this->read)($uri) : null; }
}
