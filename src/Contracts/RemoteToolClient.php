<?php

namespace Evolvex\AgentFabric\Contracts;

interface RemoteToolClient
{
    public function call(string $server,string $tool,array $arguments,array $context=[]): mixed;
}
