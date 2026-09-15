<?php

namespace Evolvex\AgentFabric\Contracts;

interface DomainPack extends AgentFabricPlugin
{
    public function domain(): string;
    public function entities(): array;
    public function workflows(): array;
}
