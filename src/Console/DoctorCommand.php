<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Models\ConfigModelCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DoctorCommand extends Command
{
    protected $signature='agent-fabric:doctor'; protected $description='Validate Agent Fabric runtime, storage, and model configuration';
    public function handle(ConfigModelCatalog $models): int
    {
        $this->info('Laravel Agent Fabric Doctor'); $this->newLine(); $ok=true;
        foreach(['ai_runs','ai_run_steps','ai_tool_executions','ai_knowledge_documents','ai_knowledge_chunks','ai_memories','ai_usage'] as $table){$exists=Schema::hasTable($table);$this->line(($exists?'✓':'✗')." table {$table}");$ok=$ok&&$exists;}
        try{DB::connection()->getPdo();$this->line('✓ database connection');}catch(\Throwable $e){$this->error('✗ database connection: '.$e->getMessage());$ok=false;}
        $count=count($models->all());$this->line(($count>0?'✓':'✗')." configured models: {$count}");if($count===0)$ok=false;
        $this->line('Embedding provider: '.(config('agent-fabric.knowledge.embedding_provider')?:'Laravel AI default'));
        return $ok?self::SUCCESS:self::FAILURE;
    }
}
