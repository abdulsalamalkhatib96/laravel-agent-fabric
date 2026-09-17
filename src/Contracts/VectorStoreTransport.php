<?php

namespace Evolvex\AgentFabric\Contracts;

interface VectorStoreTransport
{
    public function request(string $method,string $path,array $payload=[],array $headers=[]):array;
}
