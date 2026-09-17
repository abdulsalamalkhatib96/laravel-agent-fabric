<?php

namespace Evolvex\AgentFabric\Knowledge\VectorStores;

use Evolvex\AgentFabric\Contracts\VectorStoreTransport;

final class CallbackVectorStoreTransport implements VectorStoreTransport
{
    public function __construct(private readonly \Closure $callback) {}
    public function request(string $method,string $path,array $payload=[],array $headers=[]):array{return (array)($this->callback)($method,$path,$payload,$headers);}
}
