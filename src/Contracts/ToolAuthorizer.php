<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AuthorizationDecision;

interface ToolAuthorizer
{
    public function authorize(AgentTool $tool, AgentContext $context, array $arguments): AuthorizationDecision;
}
