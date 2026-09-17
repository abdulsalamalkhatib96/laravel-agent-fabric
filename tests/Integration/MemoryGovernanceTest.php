<?php

namespace Evolvex\AgentFabric\Tests\Integration;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Memory\MemoryGovernanceService;
use Evolvex\AgentFabric\Contracts\MemoryStore;
use Evolvex\AgentFabric\Tests\TestCase;

final class MemoryGovernanceTest extends TestCase
{
    protected function setUp():void{parent::setUp();$this->artisan('migrate',['--force'=>true])->run();}
    public function test_memory_is_not_persisted_until_candidate_is_approved():void
    {
        $context=new AgentContext('t','u','user');$governance=$this->app->make(MemoryGovernanceService::class);$memory=$this->app->make(MemoryStore::class);
        $id=$governance->propose($context,'support','language','ar',.9);
        self::assertSame([],$memory->recall($context,'support'));
        $governance->approve($id,'admin');
        self::assertSame('ar',$memory->recall($context,'support')[0]['value']);
    }
}
