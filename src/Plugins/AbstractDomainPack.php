<?php

namespace Evolvex\AgentFabric\Plugins;

use Evolvex\AgentFabric\Contracts\DomainPack;

abstract class AbstractDomainPack implements DomainPack
{
    public function version(): string { return '1.0.0'; }
    public function entities(): array { return []; }
    public function workflows(): array { return []; }
}
