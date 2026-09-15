<?php

namespace Evolvex\AgentFabric\Enums;

enum RunStatus: string
{
    case Created = 'created';
    case Queued = 'queued';
    case Planning = 'planning';
    case Running = 'running';
    case WaitingForTool = 'waiting_for_tool';
    case WaitingForApproval = 'waiting_for_approval';
    case WaitingForUser = 'waiting_for_user';
    case Verifying = 'verifying';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Exhausted = 'exhausted';
    case Ambiguous = 'ambiguous';

    public function terminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled, self::Exhausted, self::Ambiguous], true);
    }
}
