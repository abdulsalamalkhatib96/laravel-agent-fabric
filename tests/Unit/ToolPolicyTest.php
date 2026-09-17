<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Policies\ToolGovernanceRegistry;
use Evolvex\AgentFabric\Policies\ToolPolicy;
use PHPUnit\Framework\TestCase;

final class ToolPolicyTest extends TestCase
{
    public function test_amount_policy_can_deny_and_require_approval():void
    {
        $registry=new ToolGovernanceRegistry;
        ToolPolicy::for('refund')->maxAmount('amount',5000)->approvalAbove('amount',500)->register($registry);
        $ctx=new AgentContext('t');
        self::assertNotNull($registry->authorize('refund',$ctx,['amount'=>6000]));
        self::assertNotNull($registry->approvalReason('refund',$ctx,['amount'=>700]));
        self::assertNull($registry->approvalReason('refund',$ctx,['amount'=>100]));
    }
}
