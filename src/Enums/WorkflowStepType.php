<?php

namespace Evolvex\AgentFabric\Enums;

enum WorkflowStepType: string
{
    case Action = 'action';
    case Decision = 'decision';
    case Approval = 'approval';
    case Parallel = 'parallel';
    case Delay = 'delay';
    case Human = 'human';
    case Verify = 'verify';
}
