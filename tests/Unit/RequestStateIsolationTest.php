<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Data\AgentContext;
use PHPUnit\Framework\TestCase;

final class RequestStateIsolationTest extends TestCase
{
    public function test_agent_context_with_is_immutable_for_long_lived_workers():void
    {
        $original=new AgentContext('tenant','actor','user',['request'=>'one'],'correlation-1');
        $next=$original->with(['request'=>'two']);
        self::assertSame('one',$original->metadata['request']);self::assertSame('two',$next->metadata['request']);self::assertNotSame($original,$next);
    }
}
