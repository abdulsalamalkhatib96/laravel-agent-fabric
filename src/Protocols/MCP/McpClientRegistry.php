<?php

namespace Evolvex\AgentFabric\Protocols\MCP;

use Evolvex\AgentFabric\Contracts\McpClient;
use InvalidArgumentException;

final class McpClientRegistry
{
    private array $clients=[];
    public function register(string $name,McpClient $client): self {$this->clients[$name]=$client;return $this;}
    public function get(string $name): McpClient {return $this->clients[$name]??throw new InvalidArgumentException("Unknown MCP server [{$name}].");}
    public function tools(): array { $out=[]; foreach($this->clients as $server=>$client) foreach($client->listTools() as $tool) $out[]=['server'=>$server,'tool'=>$tool]; return $out; }
}
