<?php

namespace Evolvex\AgentFabric\Contracts;

interface RichTool extends GovernedTool
{
    public function outputSchema(): array;
    public function declaredErrors(): array;
}
