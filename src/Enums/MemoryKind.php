<?php

namespace Evolvex\AgentFabric\Enums;

enum MemoryKind: string
{
    case Working = 'working';
    case Conversation = 'conversation';
    case Episodic = 'episodic';
    case Semantic = 'semantic';
    case Procedural = 'procedural';
}
