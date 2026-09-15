<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolRisk;

interface AgentTool
{
    public function name(): string;
    public function description(): string;
    public function inputSchema(): array;
    public function risk(): ToolRisk;
    public function execute(AgentContext $context, array $arguments): ToolResult;
}
