<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Plugins\PluginContext;

interface AgentFabricPlugin
{
    public function name(): string;
    public function version(): string;
    public function register(PluginContext $context): void;
}
