<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;

interface ContextManager
{
    public function compact(string $system, string $input, array $knowledge, array $memory, array $transcript, AgentContext $context, ?int $contextWindow = null): array;
}
