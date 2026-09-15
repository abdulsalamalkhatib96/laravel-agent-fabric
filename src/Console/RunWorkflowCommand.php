<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Workflow\WorkflowEngine;
use Evolvex\AgentFabric\Workflow\WorkflowRegistry;
use Illuminate\Console\Command;

final class RunWorkflowCommand extends Command
{
    protected $signature='agent-fabric:workflow:run {workflow} {--tenant=global} {--input={}}';
    protected $description='Run a deterministic Agent Fabric workflow.';
    public function handle(WorkflowRegistry $registry,WorkflowEngine $engine): int
    {
        $input=json_decode((string)$this->option('input'),true);if(!is_array($input)){$this->error('--input must be JSON object.');return self::FAILURE;}
        $result=$engine->run($registry->get((string)$this->argument('workflow')),new AgentContext((string)$this->option('tenant')), $input);
        $this->line(json_encode(['run_id'=>$result->runId,'status'=>$result->status->value,'state'=>$result->state],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;
    }
}
