<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Protocols\A2A\AgentCard;

interface A2AClient
{
    public function discover(string $endpoint): AgentCard;
    public function delegate(string $endpoint, string $task, array $context = []): array;
}
