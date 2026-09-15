<?php

namespace Evolvex\AgentFabric\Protocols\A2A;

use Closure;
use Evolvex\AgentFabric\Contracts\A2AClient;

final class CallbackA2AClient implements A2AClient
{
    public function __construct(private readonly Closure $discoverer, private readonly Closure $delegator) {}
    public function discover(string $endpoint): AgentCard { return ($this->discoverer)($endpoint); }
    public function delegate(string $endpoint,string $task,array $context=[]): array { return ($this->delegator)($endpoint,$task,$context); }
}
