<?php

namespace Evolvex\AgentFabric\Protocols\MCP;

use Evolvex\AgentFabric\Contracts\McpClient;
use InvalidArgumentException;

final class McpClientRegistry
{
    private array $clients=[];
    private array $fingerprints=[];
    public function register(string $name,McpClient $client,?string $fingerprint=null):self{$this->clients[$name]=$client;$this->fingerprints[$name]=$fingerprint??hash('sha256',$client::class.'|'.$client->server());return $this;}
    public function get(string $name):McpClient{return $this->clients[$name]??throw new InvalidArgumentException("Unknown MCP server [{$name}].");}
    public function fingerprint(string $name):?string{return $this->fingerprints[$name]??null;}
    public function tools():array{$out=[];foreach($this->clients as $server=>$client)foreach($client->listTools() as $tool)$out[]=['server'=>$server,'fingerprint'=>$this->fingerprints[$server]??null,'tool'=>$tool];return $out;}
}
