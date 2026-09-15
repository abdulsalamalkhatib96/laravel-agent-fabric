<?php

namespace Evolvex\AgentFabric;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Approvals\DatabaseApprovalManager;
use Evolvex\AgentFabric\Budget\DefaultBudgetManager;
use Evolvex\AgentFabric\Console\DoctorCommand;
use Evolvex\AgentFabric\Console\KnowledgeSyncCommand;
use Evolvex\AgentFabric\Console\MakeAgentCommand;
use Evolvex\AgentFabric\Console\MakeToolCommand;
use Evolvex\AgentFabric\Console\RunAgentCommand;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\BudgetManager;
use Evolvex\AgentFabric\Contracts\Chunker;
use Evolvex\AgentFabric\Contracts\Embedder;
use Evolvex\AgentFabric\Contracts\KnowledgeStore;
use Evolvex\AgentFabric\Contracts\MemoryStore;
use Evolvex\AgentFabric\Contracts\ModelGateway;
use Evolvex\AgentFabric\Contracts\ModelRouter;
use Evolvex\AgentFabric\Contracts\Retriever;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Contracts\ToolAuthorizer;
use Evolvex\AgentFabric\Contracts\UsageMeter;
use Evolvex\AgentFabric\Contracts\Verifier;
use Evolvex\AgentFabric\Knowledge\DatabaseHybridRetriever;
use Evolvex\AgentFabric\Knowledge\DatabaseKnowledgeStore;
use Evolvex\AgentFabric\Knowledge\LaravelAiEmbedder;
use Evolvex\AgentFabric\Knowledge\SimpleChunker;
use Evolvex\AgentFabric\Memory\DatabaseMemoryStore;
use Evolvex\AgentFabric\Models\LaravelAiGateway;
use Evolvex\AgentFabric\Models\WeightedModelRouter;
use Evolvex\AgentFabric\Repositories\DatabaseRunRepository;
use Evolvex\AgentFabric\Tools\DefaultToolAuthorizer;
use Evolvex\AgentFabric\Usage\DatabaseUsageMeter;
use Evolvex\AgentFabric\Verification\GroundedVerifier;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\ServiceProvider;

final class AgentFabricServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agent-fabric.php','agent-fabric');
        $this->app->singleton(AgentRegistry::class);
        $this->app->bind(ConnectionInterface::class, fn ($app) => $app['db']->connection());
        $this->app->bind(ModelGateway::class,LaravelAiGateway::class);
        $this->app->bind(ModelRouter::class,WeightedModelRouter::class);
        $this->app->bind(Embedder::class,LaravelAiEmbedder::class);
        $this->app->bind(Retriever::class,DatabaseHybridRetriever::class);
        $this->app->bind(KnowledgeStore::class,DatabaseKnowledgeStore::class);
        $this->app->bind(MemoryStore::class,DatabaseMemoryStore::class);
        $this->app->bind(RunRepository::class,DatabaseRunRepository::class);
        $this->app->bind(UsageMeter::class,DatabaseUsageMeter::class);
        $this->app->bind(BudgetManager::class,DefaultBudgetManager::class);
        $this->app->bind(Verifier::class,GroundedVerifier::class);
        $this->app->bind(ToolAuthorizer::class,DefaultToolAuthorizer::class);
        $this->app->bind(ApprovalManager::class,DatabaseApprovalManager::class);
        $this->app->singleton(Chunker::class,fn()=>new SimpleChunker((int)config('agent-fabric.knowledge.chunk_size',1200),(int)config('agent-fabric.knowledge.chunk_overlap',150)));
        $this->app->singleton(AgentFabricManager::class);
    }
    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/agent-fabric.php'=>config_path('agent-fabric.php')],'agent-fabric-config');
        $this->publishes([__DIR__.'/../database/migrations'=>database_path('migrations')],'agent-fabric-migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        foreach(config('agent-fabric.agents',[]) as $name=>$class)$this->app->make(AgentRegistry::class)->register((string)$name,$class);
        if($this->app->runningInConsole())$this->commands([DoctorCommand::class,KnowledgeSyncCommand::class,MakeAgentCommand::class,MakeToolCommand::class,RunAgentCommand::class]);
    }
}
