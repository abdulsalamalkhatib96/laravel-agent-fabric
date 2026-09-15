<?php

namespace App\Ai\Agents;

use App\Ai\Tools\FindOrder;
use App\Ai\Tools\RefundOrder;
use Evolvex\AgentFabric\Agents\AgentBlueprint;

final class SupportAgent extends AgentBlueprint
{
    public function name(): string { return 'support'; }
    public function version(): string { return '1.0.0'; }
    public function goal(): string { return 'Resolve customer support cases end-to-end using verified business data.'; }

    public function instructions(): string
    {
        return <<<'PROMPT'
Resolve the customer's issue rather than merely describing what they could do.
Never claim an operation succeeded unless a successful tool result proves it.
Ask for clarification when an order or customer cannot be uniquely identified.
PROMPT;
    }

    public function tools(): array { return [FindOrder::class, RefundOrder::class]; }
    public function knowledgeSources(): array { return ['support-policies', 'product-catalog']; }
    public function requiredCapabilities(): array { return ['reasoning', 'structured_output']; }
}
