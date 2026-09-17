<?php

namespace Evolvex\AgentFabric\Tests\Integration;

use Evolvex\AgentFabric\Contracts\RemoteOperationReconciler;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RemoteOperationOutcome;
use Evolvex\AgentFabric\RemoteOperations\RemoteOperationCoordinator;
use Evolvex\AgentFabric\Tests\TestCase;
use RuntimeException;

final class RemoteOperationReconciliationTest extends TestCase
{
    protected function setUp():void{parent::setUp();$this->artisan('migrate',['--force'=>true])->run();}

    public function test_timeout_is_reconciled_without_reexecuting_remote_side_effect():void
    {
        $coordinator=$this->app->make(RemoteOperationCoordinator::class);$context=new AgentContext('tenant');$sends=0;
        $reconciler=new class implements RemoteOperationReconciler{
            public function reconcile(string $operationName,string $reconciliationKey,AgentContext $context,array $metadata=[]):RemoteOperationOutcome{return RemoteOperationOutcome::succeeded(['confirmed'=>true],'provider-55',['provider:55']);}
        };
        $first=$coordinator->execute('run','withdraw','idem','withdraw-55',$context,function()use(&$sends){$sends++;throw new RuntimeException('connection timeout after send');},$reconciler);
        $second=$coordinator->execute('run','withdraw','idem','withdraw-55',$context,function()use(&$sends){$sends++;return RemoteOperationOutcome::succeeded();},$reconciler);
        self::assertSame('succeeded',$first->status);self::assertSame('succeeded',$second->status);self::assertSame(1,$sends,'Persisted reconciliation result must prevent duplicate provider execution.');
    }
}
