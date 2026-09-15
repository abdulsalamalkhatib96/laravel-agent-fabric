<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelRequest;

interface ModelRouter
{
    public function route(ModelRequest $request): ModelProfile;
}
