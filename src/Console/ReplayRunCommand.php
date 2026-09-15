<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Deployment\ReplayService;
use Illuminate\Console\Command;

final class ReplayRunCommand extends Command
{
    protected $signature='agent-fabric:replay {run} {--agent=}';
    protected $description='Replay an existing agent run with side effects disabled.';
    public function handle(ReplayService $replay): int
    {
        $result=$replay->replay((string)$this->argument('run'),$this->option('agent')?:null);
        $this->line(json_encode(['run_id'=>$result->runId,'status'=>$result->status->value,'answer'=>$result->answer],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;
    }
}
