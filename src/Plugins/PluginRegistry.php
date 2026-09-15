<?php

namespace Evolvex\AgentFabric\Plugins;

use Evolvex\AgentFabric\Contracts\AgentFabricPlugin;

final class PluginRegistry
{
    private array $plugins=[];
    public function __construct(private readonly PluginContext $context){}
    public function register(AgentFabricPlugin|string $plugin): self{ $instance=is_string($plugin)?app($plugin):$plugin; $instance->register($this->context); $this->plugins[$instance->name()]=$instance; return $this; }
    public function all(): array{return $this->plugins;}
}
