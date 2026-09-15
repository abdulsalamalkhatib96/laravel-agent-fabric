<?php

namespace Evolvex\AgentFabric\Protocols\A2A;

final readonly class AgentCard
{
    public function __construct(public string $name,public string $endpoint,public array $skills=[],public array $capabilities=[],public array $security=[],public array $metadata=[]){ }
}
