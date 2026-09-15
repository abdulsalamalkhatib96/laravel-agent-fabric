<?php

namespace Evolvex\AgentFabric\Enums;

enum StepType: string
{
    case Prompt = 'prompt';
    case Plan = 'plan';
    case Retrieve = 'retrieve';
    case ModelCall = 'model_call';
    case ToolCall = 'tool_call';
    case ToolResult = 'tool_result';
    case Decision = 'decision';
    case Approval = 'approval';
    case SubAgent = 'sub_agent';
    case Verification = 'verification';
    case Response = 'response';
    case MemoryWrite = 'memory_write';
}
