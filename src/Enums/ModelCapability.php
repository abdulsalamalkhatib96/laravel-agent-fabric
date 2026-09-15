<?php

namespace Evolvex\AgentFabric\Enums;

enum ModelCapability: string
{
    case Reasoning = 'reasoning';
    case Tools = 'tools';
    case StructuredOutput = 'structured_output';
    case Vision = 'vision';
    case Audio = 'audio';
    case Embeddings = 'embeddings';
    case LongContext = 'long_context';
}
