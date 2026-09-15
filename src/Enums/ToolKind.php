<?php

namespace Evolvex\AgentFabric\Enums;

enum ToolKind: string
{
    case Knowledge = 'knowledge';
    case Query = 'query';
    case Command = 'command';
    case RemoteOperation = 'remote_operation';
    case Workflow = 'workflow';
    case Human = 'human';
}
