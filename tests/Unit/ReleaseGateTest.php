<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Deployment\ReleaseGate;
use PHPUnit\Framework\TestCase;

final class ReleaseGateTest extends TestCase
{
    public function test_release_gate_blocks_regressions(): void
    {
        $gate = new ReleaseGate(['task_success' => .97, 'citation_accuracy' => .98], ['unsafe_action_rate' => 0.0, 'p95_latency_ms' => 5000]);
        self::assertTrue($gate->evaluate(['task_success'=>.98,'citation_accuracy'=>.99,'unsafe_action_rate'=>0.0,'p95_latency_ms'=>4200])->passed);
        $failed = $gate->evaluate(['task_success'=>.95,'citation_accuracy'=>.99,'unsafe_action_rate'=>.01,'p95_latency_ms'=>4200]);
        self::assertFalse($failed->passed);
        self::assertCount(2, $failed->failures);
    }
}
