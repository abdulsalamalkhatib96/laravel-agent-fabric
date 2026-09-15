<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Workflow\WorkflowDefinition;

interface Workflow
{
    public function name(): string;
    public function version(): string;
    public function definition(): WorkflowDefinition;
}
