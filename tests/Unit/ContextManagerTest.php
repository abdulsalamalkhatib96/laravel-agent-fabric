<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Context\DefaultContextManager;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RetrievalResult;
use Evolvex\AgentFabric\Tests\TestCase;

final class ContextManagerTest extends TestCase
{
    public function test_context_is_compacted_to_budget_and_keeps_highest_scoring_knowledge():void
    {
        $knowledge=[new RetrievalResult(str_repeat('A',400),.9,'d1','c1'),new RetrievalResult(str_repeat('B',400),.2,'d2','c2')];
        $result=(new DefaultContextManager)->compact('system','input',$knowledge,[],[['role'=>'user','content'=>str_repeat('x',400)]],new AgentContext('t'),300);
        self::assertLessThanOrEqual($result['budget_tokens'],$result['estimated_tokens']);
        self::assertSame('c1',$result['knowledge'][0]->chunkId);
    }
}
