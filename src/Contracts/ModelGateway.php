<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelRequest;
use Evolvex\AgentFabric\Data\ModelResponse;

interface ModelGateway
{
    public function generate(ModelRequest $request, ModelProfile $profile): ModelResponse;
}
