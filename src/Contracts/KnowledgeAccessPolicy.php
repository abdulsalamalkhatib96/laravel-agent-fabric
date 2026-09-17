<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;

interface KnowledgeAccessPolicy
{
    public function allows(AgentContext $context, array $metadata): bool;
}
