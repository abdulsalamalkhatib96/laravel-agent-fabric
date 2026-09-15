<?php

namespace Evolvex\AgentFabric\Facades;

use Illuminate\Support\Facades\Facade;

final class AgentFabric extends Facade
{
    protected static function getFacadeAccessor(): string { return \Evolvex\AgentFabric\AgentFabricManager::class; }
}
