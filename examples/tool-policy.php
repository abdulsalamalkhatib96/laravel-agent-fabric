<?php

use Evolvex\AgentFabric\Facades\AgentFabric;
use Evolvex\AgentFabric\Policies\ToolPolicy;

AgentFabric::toolPolicy(
    ToolPolicy::for('refund-order')
        ->denyWhen(fn ($context, $arguments) => ($arguments['currency'] ?? 'AED') !== 'AED')
        ->approvalAbove('amount', 1000)
);
