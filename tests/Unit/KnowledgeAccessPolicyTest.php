<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Knowledge\DefaultKnowledgeAccessPolicy;
use PHPUnit\Framework\TestCase;

final class KnowledgeAccessPolicyTest extends TestCase
{
    public function test_acl_limits_role_and_department():void
    {
        $policy=new DefaultKnowledgeAccessPolicy;
        self::assertTrue($policy->allows(new AgentContext('t','1','user',['role'=>'support','department'=>'ops']),['_acl'=>['roles'=>['support'],'departments'=>['ops']]]));
        self::assertFalse($policy->allows(new AgentContext('t','2','user',['role'=>'sales','department'=>'ops']),['_acl'=>['roles'=>['support']]]));
    }
}
