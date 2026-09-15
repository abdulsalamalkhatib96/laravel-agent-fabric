<?php

namespace App\Ai\Tools;

use App\Models\Order;
use Evolvex\AgentFabric\Contracts\AgentTool;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolRisk;

final class FindOrder implements AgentTool
{
    public function name(): string { return 'find_order'; }
    public function description(): string { return 'Find an order belonging to the active tenant and customer.'; }
    public function risk(): ToolRisk { return ToolRisk::Read; }
    public function inputSchema(): array
    {
        return ['type'=>'object','properties'=>['order_id'=>['type'=>'string']],'required'=>['order_id']];
    }

    public function execute(AgentContext $context, array $arguments): ToolResult
    {
        $order = Order::query()
            ->where('tenant_id', $context->tenantId)
            ->when($context->actorId, fn ($q) => $q->where('customer_id', $context->actorId))
            ->find($arguments['order_id']);

        return $order
            ? ToolResult::success($order->only(['id','status','total','currency']), evidence: ["order:{$order->id}"])
            : ToolResult::failure('Order not found or not accessible.');
    }
}
