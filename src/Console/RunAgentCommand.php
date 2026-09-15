<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\AgentFabricManager;
use Illuminate\Console\Command;

final class RunAgentCommand extends Command
{
    protected $signature='agent-fabric:run {agent} {input} {--tenant=default} {--actor=}'; protected $description='Run a registered Agent Fabric agent';
    public function handle(AgentFabricManager $fabric): int
    {
        $result=$fabric->agent((string)$this->argument('agent'))->tenant((string)$this->option('tenant'))->actor($this->option('actor'))->ask((string)$this->argument('input'));
        $this->line(json_encode(['run_id'=>$result->runId,'status'=>$result->status->value,'answer'=>$result->answer,'metadata'=>$result->metadata],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));return self::SUCCESS;
    }
}
