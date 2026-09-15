<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Enums\ToolRisk;
use PHPUnit\Framework\TestCase;

final class ToolRiskTest extends TestCase
{
    public function test_financial_and_destructive_tools_require_approval(): void
    {
        self::assertTrue(ToolRisk::Financial->requiresApprovalByDefault());
        self::assertTrue(ToolRisk::Destructive->requiresApprovalByDefault());
        self::assertFalse(ToolRisk::Read->requiresApprovalByDefault());
    }
}
