<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Policies\PolicyBuilder;
use PHPUnit\Framework\TestCase;

final class PolicyBuilderTest extends TestCase
{
    public function test_rule_policy_denies_when_condition_fails(): void
    {
        $policy = PolicyBuilder::make()
            ->allowWhen('tool', fn (AgentContext $ctx, array $payload) => $payload['amount'] <= 500, 'Amount too high.')
            ->build();

        self::assertTrue($policy->evaluate(new AgentContext('t1'), 'tool', ['amount' => 100])->allowed);
        $denied = $policy->evaluate(new AgentContext('t1'), 'tool', ['amount' => 700]);
        self::assertFalse($denied->allowed);
        self::assertSame('Amount too high.', $denied->reason);
    }
}
