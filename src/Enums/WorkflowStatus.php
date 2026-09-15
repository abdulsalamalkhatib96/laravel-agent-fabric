<?php

namespace Evolvex\AgentFabric\Enums;

enum WorkflowStatus: string
{
    case Created = 'created';
    case Running = 'running';
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Compensating = 'compensating';
    case Compensated = 'compensated';
}
