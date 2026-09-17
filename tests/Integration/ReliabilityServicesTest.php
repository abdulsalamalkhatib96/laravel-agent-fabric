<?php

namespace Evolvex\AgentFabric\Tests\Integration;

use Evolvex\AgentFabric\Contracts\Inbox;
use Evolvex\AgentFabric\Contracts\LeaseManager;
use Evolvex\AgentFabric\Contracts\Outbox;
use Evolvex\AgentFabric\Tests\TestCase;

final class ReliabilityServicesTest extends TestCase
{
    protected function setUp():void{parent::setUp();$this->artisan('migrate',['--force'=>true])->run();}

    public function test_lease_is_exclusive_and_can_be_released():void
    {
        $leases=$this->app->make(LeaseManager::class);
        self::assertTrue($leases->acquire('test','same','worker-a',30));
        self::assertFalse($leases->acquire('test','same','worker-b',30));
        self::assertSame('worker-a',$leases->owner('test','same'));
        $leases->release('test','same','worker-a');
        self::assertTrue($leases->acquire('test','same','worker-b',30));
    }

    public function test_outbox_deduplicates_and_inbox_is_idempotent():void
    {
        $outbox=$this->app->make(Outbox::class);$inbox=$this->app->make(Inbox::class);
        $a=$outbox->add('order.updated',['id'=>1],'same','tenant');$b=$outbox->add('order.updated',['id'=>1],'same','tenant');
        self::assertSame($a,$b);$claimed=$outbox->claim('worker',10,30);self::assertCount(1,$claimed);$outbox->acknowledge($a,'worker');
        self::assertTrue($inbox->begin('consumer','message-1','tenant'));self::assertFalse($inbox->begin('consumer','message-1','tenant'));$inbox->complete('consumer','message-1');
    }
}
