<?php

namespace Evolvex\AgentFabric;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Approvals\DatabaseApprovalManager;
use Evolvex\AgentFabric\Budget\DefaultBudgetManager;
use Evolvex\AgentFabric\Channels\ChannelRegistry;
use Evolvex\AgentFabric\Connectors\ConnectorRegistry;
use Evolvex\AgentFabric\Console\DoctorCommand;
use Evolvex\AgentFabric\Capabilities\CapabilityMatrix;
use Evolvex\AgentFabric\Deployment\ReplayService;
use Evolvex\AgentFabric\Deployment\DatabaseDeploymentManager;
use Evolvex\AgentFabric\Deployment\AgentVersionFingerprint;
use Evolvex\AgentFabric\Console\RunWorkflowCommand;
use Evolvex\AgentFabric\Console\ReplayRunCommand;
use Evolvex\AgentFabric\Console\MakeWorkflowCommand;
use Evolvex\AgentFabric\Console\CapabilitiesCommand;
use Evolvex\AgentFabric\Console\KnowledgeSyncCommand;
use Evolvex\AgentFabric\Console\MakeAgentCommand;
use Evolvex\AgentFabric\Console\MakeToolCommand;
use Evolvex\AgentFabric\Console\RunAgentCommand;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\BudgetManager;
use Evolvex\AgentFabric\Contracts\Chunker;
use Evolvex\AgentFabric\Contracts\Embedder;
use Evolvex\AgentFabric\Contracts\EntityStore;
use Evolvex\AgentFabric\Contracts\KnowledgeStore;
use Evolvex\AgentFabric\Contracts\MemoryStore;
use Evolvex\AgentFabric\Contracts\ModelGateway;
use Evolvex\AgentFabric\Contracts\ModelRouter;
use Evolvex\AgentFabric\Contracts\Retriever;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Contracts\ToolAuthorizer;
use Evolvex\AgentFabric\Contracts\TraceRecorder;
use Evolvex\AgentFabric\Contracts\UsageMeter;
use Evolvex\AgentFabric\Contracts\Verifier;
use Evolvex\AgentFabric\Knowledge\DatabaseEntityStore;
use Evolvex\AgentFabric\Knowledge\DatabaseHybridRetriever;
use Evolvex\AgentFabric\Knowledge\DatabaseKnowledgeStore;
use Evolvex\AgentFabric\Knowledge\LaravelAiEmbedder;
use Evolvex\AgentFabric\Knowledge\SimpleChunker;
use Evolvex\AgentFabric\Memory\DatabaseMemoryStore;
use Evolvex\AgentFabric\Models\LaravelAiGateway;
use Evolvex\AgentFabric\Models\WeightedModelRouter;
use Evolvex\AgentFabric\Observability\DatabaseTraceRecorder;
use Evolvex\AgentFabric\Plugins\PluginContext;
use Evolvex\AgentFabric\Plugins\PluginRegistry;
use Evolvex\AgentFabric\Protocols\MCP\McpClientRegistry;
use Evolvex\AgentFabric\Repositories\DatabaseRunRepository;
use Evolvex\AgentFabric\Tools\DefaultToolAuthorizer;
use Evolvex\AgentFabric\Usage\DatabaseUsageMeter;
use Evolvex\AgentFabric\Verification\GroundedVerifier;
use Evolvex\AgentFabric\Workflow\WorkflowEngine;
use Evolvex\AgentFabric\Workflow\WorkflowRegistry;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\ServiceProvider;

final class AgentFabricServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agent-fabric.php','agent-fabric');
        foreach([AgentRegistry::class,ConnectorRegistry::class,ChannelRegistry::class,WorkflowRegistry::class,McpClientRegistry::class] as $singleton)$this->app->singleton($singleton);
        $this->app->bind(ConnectionInterface::class,fn($app)=>$app['db']->connection());
        $this->app->bind(ModelGateway::class,LaravelAiGateway::class);
        $this->app->bind(ModelRouter::class,WeightedModelRouter::class);
        $this->app->bind(Embedder::class,LaravelAiEmbedder::class);
        $this->app->bind(Retriever::class,DatabaseHybridRetriever::class);
        $this->app->bind(KnowledgeStore::class,DatabaseKnowledgeStore::class);
        $this->app->bind(EntityStore::class,DatabaseEntityStore::class);
        $this->app->bind(MemoryStore::class,DatabaseMemoryStore::class);
        $this->app->bind(RunRepository::class,DatabaseRunRepository::class);
        $this->app->bind(UsageMeter::class,DatabaseUsageMeter::class);
        $this->app->bind(BudgetManager::class,DefaultBudgetManager::class);
        $this->app->bind(Verifier::class,GroundedVerifier::class);
        $this->app->bind(TraceRecorder::class,DatabaseTraceRecorder::class);
        $this->app->bind(ToolAuthorizer::class,DefaultToolAuthorizer::class);
        $this->app->bind(ApprovalManager::class,DatabaseApprovalManager::class);
        $this->app->singleton(Chunker::class,fn()=>new SimpleChunker((int)config('agent-fabric.knowledge.chunk_size',1200),(int)config('agent-fabric.knowledge.chunk_overlap',150)));
        $this->app->singleton(WorkflowEngine::class);
        $this->app->singleton(CapabilityMatrix::class);
        $this->app->singleton(AgentVersionFingerprint::class);
        $this->app->singleton(DatabaseDeploymentManager::class);
        $this->app->singleton(ReplayService::class);
        $this->app->singleton(PluginContext::class,fn($app)=>new PluginContext($app->make(AgentRegistry::class),$app->make(ConnectorRegistry::class),$app->make(ChannelRegistry::class),$app->make(WorkflowRegistry::class)));
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(AgentFabricManager::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/agent-fabric.php'=>config_path('agent-fabric.php')],'agent-fabric-config');
        $this->publishes([__DIR__.'/../database/migrations'=>database_path('migrations')],'agent-fabric-migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $agents=$this->app->make(AgentRegistry::class); foreach(config('agent-fabric.agents',[]) as $name=>$class)$agents->register((string)$name,$class);
        $workflows=$this->app->make(WorkflowRegistry::class); foreach(config('agent-fabric.workflows',[]) as $class)$workflows->register($class);
        $connectors=$this->app->make(ConnectorRegistry::class); foreach(config('agent-fabric.connectors',[]) as $class){$instance=$this->app->make($class);$connectors->register($instance);}
        $channels=$this->app->make(ChannelRegistry::class); foreach(config('agent-fabric.channels',[]) as $class){$instance=$this->app->make($class);$channels->register($instance);}
        $plugins=$this->app->make(PluginRegistry::class); foreach(config('agent-fabric.plugins',[]) as $class)$plugins->register($class);
        if($this->app->runningInConsole())$this->commands([DoctorCommand::class,KnowledgeSyncCommand::class,MakeAgentCommand::class,MakeToolCommand::class,MakeWorkflowCommand::class,RunAgentCommand::class,RunWorkflowCommand::class,ReplayRunCommand::class,CapabilitiesCommand::class]);
    }
}
