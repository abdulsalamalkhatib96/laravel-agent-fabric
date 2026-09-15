<?php

namespace App\Ai\Tools;

use Evolvex\AgentFabric\Contracts\AgentTool;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolRisk;

final class RefundOrder implements AgentTool
{
    public function name(): string { return 'refund_order'; }
    public function description(): string { return 'Refund an eligible order. Financial operations require human approval by default.'; }
    public function risk(): ToolRisk { return ToolRisk::Financial; }
    public function inputSchema(): array
    {
        return [
            'type'=>'object',
            'properties'=>[
                'order_id'=>['type'=>'string'],
                'amount'=>['type'=>'number'],
                'reason'=>['type'=>'string'],
            ],
            'required'=>['order_id','amount','reason'],
        ];
    }

    public function execute(AgentContext $context, array $arguments): ToolResult
    {
        // Call your idempotent application/domain service here. Do not call a provider directly without ambiguity handling.
        return ToolResult::success(
            ['order_id'=>$arguments['order_id'],'amount'=>$arguments['amount'],'status'=>'accepted'],
            evidence: ["refund:{$arguments['order_id']}"]
        );
    }
}
