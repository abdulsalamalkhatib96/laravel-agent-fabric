<?php

namespace Evolvex\AgentFabric\Enums;

enum ToolRisk: string
{
    case Read = 'read';
    case Write = 'write';
    case Destructive = 'destructive';
    case Financial = 'financial';
    case External = 'external';
    case Privileged = 'privileged';

    public function requiresApprovalByDefault(): bool
    {
        return in_array($this, [self::Destructive, self::Financial, self::Privileged], true);
    }
}
