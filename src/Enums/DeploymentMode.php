<?php

namespace Evolvex\AgentFabric\Enums;

enum DeploymentMode: string
{
    case Active = 'active';
    case Canary = 'canary';
    case Shadow = 'shadow';
    case Disabled = 'disabled';
}
