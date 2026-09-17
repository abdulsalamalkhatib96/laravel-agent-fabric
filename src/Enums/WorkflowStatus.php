<?php

namespace Evolvex\AgentFabric\Enums;

enum WorkflowStatus: string
{
    case Created = 'created';
    case Running = 'running';
    case Waiting = 'waiting';
    case Retrying = 'retrying';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Compensating = 'compensating';
    case Compensated = 'compensated';
    case Ambiguous = 'ambiguous';

    public function terminal(): bool
    {
        return in_array($this,[self::Completed,self::Failed,self::Cancelled,self::Compensated,self::Ambiguous],true);
    }
}
