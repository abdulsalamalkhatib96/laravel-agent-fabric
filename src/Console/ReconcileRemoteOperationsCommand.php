<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\RemoteOperations\ReconcilerRegistry;
use Evolvex\AgentFabric\RemoteOperations\RemoteOperationCoordinator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

final class ReconcileRemoteOperationsCommand extends Command
{
    protected $signature='agent-fabric:reconcile {--limit=100}';
    protected $description='Reconcile ambiguous remote operations using registered reconcilers';
    public function handle(ConnectionInterface $db,RemoteOperationCoordinator $coordinator,ReconcilerRegistry $registry):int
    {
        $rows=$db->table('ai_remote_operations')->where('status','ambiguous')->orderBy('updated_at')->limit(max(1,(int)$this->option('limit')))->get();$resolved=0;$remaining=0;
        foreach($rows as $row){$reconciler=$registry->get((string)$row->operation_name);if(!$reconciler){$remaining++;continue;}$outcome=$coordinator->reconcile((string)$row->id,new AgentContext((string)$row->tenant_id),$reconciler);$outcome->final()?$resolved++:$remaining++;}
        $this->info("Resolved: {$resolved}; still ambiguous: {$remaining}");return self::SUCCESS;
    }
}
