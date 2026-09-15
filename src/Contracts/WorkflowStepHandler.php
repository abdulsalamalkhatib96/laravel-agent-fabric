<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Workflow\WorkflowStepResult;

interface WorkflowStepHandler
{
    public function handle(AgentContext $context, array $input, array $state): WorkflowStepResult;
}
