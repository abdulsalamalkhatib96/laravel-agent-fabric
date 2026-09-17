<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Models\ModelFailureClassifier;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModelFailureClassifierTest extends TestCase
{
    public function test_retryable_failures_are_classified():void
    {
        $c=new ModelFailureClassifier;
        self::assertSame('rate_limit',$c->classify(new RuntimeException('HTTP 429 rate limit exceeded')));
        self::assertTrue($c->retryable('timeout'));
        self::assertFalse($c->retryable('safety_refusal'));
    }
}
