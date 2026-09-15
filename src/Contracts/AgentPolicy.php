<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AuthorizationDecision;

interface AgentPolicy
{
    public function evaluate(AgentContext $context, string $phase, array $payload = []): AuthorizationDecision;
}
