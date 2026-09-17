<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Models\ConfigModelCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DoctorCommand extends Command
{
    protected $signature='agent-fabric:doctor';
    protected $description='Validate Agent Fabric runtime, reliability, storage, governance, and model configuration';

    public function handle(ConfigModelCatalog $models):int
    {
        $this->info('Laravel Agent Fabric Doctor');$this->newLine();$ok=true;
        $groups=[
            'runtime'=>['ai_runs','ai_run_steps','ai_tool_executions','ai_approvals','ai_usage'],
            'knowledge'=>['ai_knowledge_documents','ai_knowledge_chunks','ai_memories'],
            'workflow'=>['ai_workflow_runs','ai_workflow_steps'],
            'reliability'=>['ai_leases','ai_outbox','ai_inbox','ai_remote_operations','ai_circuit_breakers','ai_quota_counters'],
            'control-plane'=>['ai_model_metrics','ai_model_observations','ai_prompt_versions','ai_deployments','ai_audit_logs'],
        ];
        foreach($groups as $group=>$tables){$this->line("[{$group}]");foreach($tables as $table){$exists=Schema::hasTable($table);$this->line(($exists?'✓':'✗')." {$table}");$ok=$ok&&$exists;}}
        try{DB::connection()->getPdo();$this->line('✓ database connection');}catch(\Throwable $e){$this->error('✗ database connection: '.$e->getMessage());$ok=false;}
        $count=count($models->all());$this->line(($count>0?'✓':'✗')." configured models: {$count}");if($count===0)$ok=false;
        $this->line('Embedding provider: '.(config('agent-fabric.knowledge.embedding_provider')?:'Laravel AI default'));
        $this->line('OpenTelemetry: '.(config('agent-fabric.observability.opentelemetry',true)?'enabled (exports when OpenTelemetry API is installed)':'disabled'));
        $this->line('A2A scoped delegation: '.(config('agent-fabric.protocols.a2a.require_delegation_token',false)?'required':'optional'));
        $this->line('MCP registration policy: '.(config('agent-fabric.protocols.mcp.require_registered_server',false)?'strict':'backward-compatible'));
        return $ok?self::SUCCESS:self::FAILURE;
    }
}
