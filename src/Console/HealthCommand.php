<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Observability\QueueHealthService;
use Illuminate\Console\Command;

final class HealthCommand extends Command
{
    protected $signature='agent-fabric:health';
    protected $description='Show Agent Fabric runtime, workflow, outbox, reconciliation, and queue health';
    public function handle(QueueHealthService $health):int{$this->line(json_encode($health->snapshot(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;}
}
