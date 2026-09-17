<?php

namespace Evolvex\AgentFabric;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Approvals\DatabaseApprovalManager;
use Evolvex\AgentFabric\Budget\DefaultBudgetManager;
use Evolvex\AgentFabric\Capabilities\CapabilityMatrix;
use Evolvex\AgentFabric\Channels\ChannelRegistry;
use Evolvex\AgentFabric\Connectors\ConnectorRegistry;
use Evolvex\AgentFabric\Console\CapabilitiesCommand;
use Evolvex\AgentFabric\Console\DoctorCommand;
use Evolvex\AgentFabric\Console\KnowledgeSyncCommand;
use Evolvex\AgentFabric\Console\MakeAgentCommand;
use Evolvex\AgentFabric\Console\MakeToolCommand;
use Evolvex\AgentFabric\Console\MakeWorkflowCommand;
use Evolvex\AgentFabric\Console\ReplayRunCommand;
use Evolvex\AgentFabric\Console\ReconcileRemoteOperationsCommand;
use Evolvex\AgentFabric\Console\ProcessOutboxCommand;
use Evolvex\AgentFabric\Console\HealthCommand;
use Evolvex\AgentFabric\Console\RunAgentCommand;
use Evolvex\AgentFabric\Console\RunWorkflowCommand;
use Evolvex\AgentFabric\Context\DefaultContextManager;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\BudgetManager;
use Evolvex\AgentFabric\Contracts\Chunker;
use Evolvex\AgentFabric\Contracts\CircuitBreaker;
use Evolvex\AgentFabric\Contracts\ContextManager;
use Evolvex\AgentFabric\Contracts\Embedder;
use Evolvex\AgentFabric\Contracts\EntityStore;
use Evolvex\AgentFabric\Contracts\Inbox;
use Evolvex\AgentFabric\Contracts\KnowledgeAccessPolicy;
use Evolvex\AgentFabric\Contracts\KnowledgeStore;
use Evolvex\AgentFabric\Contracts\LeaseManager;
use Evolvex\AgentFabric\Contracts\MemoryStore;
use Evolvex\AgentFabric\Contracts\ModelGateway;
use Evolvex\AgentFabric\Contracts\ModelRouter;
use Evolvex\AgentFabric\Contracts\ModelTelemetry;
use Evolvex\AgentFabric\Contracts\Outbox;
use Evolvex\AgentFabric\Contracts\PiiDetector;
use Evolvex\AgentFabric\Contracts\PromptInjectionDetector;
use Evolvex\AgentFabric\Contracts\QuotaManager;
use Evolvex\AgentFabric\Contracts\Retriever;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Contracts\TelemetryExporter;
use Evolvex\AgentFabric\Contracts\ToolAuthorizer;
use Evolvex\AgentFabric\Contracts\TraceRecorder;
use Evolvex\AgentFabric\Contracts\UsageMeter;
use Evolvex\AgentFabric\Contracts\Verifier;
use Evolvex\AgentFabric\Deployment\AgentVersionFingerprint;
use Evolvex\AgentFabric\Deployment\DatabaseDeploymentManager;
use Evolvex\AgentFabric\Deployment\PromptVersionStore;
use Evolvex\AgentFabric\Deployment\ReplayService;
use Evolvex\AgentFabric\Identity\DelegationService;
use Evolvex\AgentFabric\Knowledge\DatabaseEntityStore;
use Evolvex\AgentFabric\Knowledge\DatabaseHybridRetriever;
use Evolvex\AgentFabric\Knowledge\DatabaseKnowledgeStore;
use Evolvex\AgentFabric\Knowledge\DefaultKnowledgeAccessPolicy;
use Evolvex\AgentFabric\Knowledge\LaravelAiEmbedder;
use Evolvex\AgentFabric\Knowledge\SimpleChunker;
use Evolvex\AgentFabric\Memory\DatabaseMemoryStore;
use Evolvex\AgentFabric\Memory\MemoryGovernanceService;
use Evolvex\AgentFabric\Messaging\DatabaseInbox;
use Evolvex\AgentFabric\Messaging\DatabaseOutbox;
use Evolvex\AgentFabric\Models\ConfigModelCatalog;
use Evolvex\AgentFabric\Models\LaravelAiGateway;
use Evolvex\AgentFabric\Models\ModelFailureClassifier;
use Evolvex\AgentFabric\Models\ResilientModelGateway;
use Evolvex\AgentFabric\Models\WeightedModelRouter;
use Evolvex\AgentFabric\Observability\CompositeTelemetryExporter;
use Evolvex\AgentFabric\Observability\DatabaseModelTelemetry;
use Evolvex\AgentFabric\Observability\DatabaseTraceRecorder;
use Evolvex\AgentFabric\Observability\OpenTelemetryExporter;
use Evolvex\AgentFabric\Plugins\PluginContext;
use Evolvex\AgentFabric\Policies\ToolGovernanceRegistry;
use Evolvex\AgentFabric\Plugins\PluginRegistry;
use Evolvex\AgentFabric\Protocols\MCP\McpClientRegistry;
use Evolvex\AgentFabric\Protocols\MCP\McpServerPolicy;
use Evolvex\AgentFabric\Quotas\DatabaseQuotaManager;
use Evolvex\AgentFabric\Reliability\DatabaseCircuitBreaker;
use Evolvex\AgentFabric\Reliability\DatabaseLeaseManager;
use Evolvex\AgentFabric\RemoteOperations\RemoteOperationCoordinator;
use Evolvex\AgentFabric\RemoteOperations\ReconcilerRegistry;
use Evolvex\AgentFabric\Observability\QueueHealthService;
use Evolvex\AgentFabric\Repositories\DatabaseRunRepository;
use Evolvex\AgentFabric\Security\HeuristicPromptInjectionDetector;
use Evolvex\AgentFabric\Security\RegexPiiDetector;
use Evolvex\AgentFabric\Tools\DefaultToolAuthorizer;
use Evolvex\AgentFabric\Usage\DatabaseUsageMeter;
use Evolvex\AgentFabric\Verification\GroundedVerifier;
use Evolvex\AgentFabric\Workflow\WorkflowEngine;
use Evolvex\AgentFabric\Workflow\WorkflowRegistry;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\ServiceProvider;

final class AgentFabricServiceProvider extends ServiceProvider
{
    public function register():void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agent-fabric.php','agent-fabric');
        foreach([AgentRegistry::class,ConnectorRegistry::class,ChannelRegistry::class,WorkflowRegistry::class,McpClientRegistry::class,ConfigModelCatalog::class,ModelFailureClassifier::class,ToolGovernanceRegistry::class,ReconcilerRegistry::class] as $singleton)$this->app->singleton($singleton);
        $this->app->bind(ConnectionInterface::class,fn($app)=>$app['db']->connection());

        $this->app->bind(LeaseManager::class,DatabaseLeaseManager::class);
        $this->app->bind(CircuitBreaker::class,DatabaseCircuitBreaker::class);
        $this->app->bind(QuotaManager::class,DatabaseQuotaManager::class);
        $this->app->bind(Outbox::class,DatabaseOutbox::class);
        $this->app->bind(Inbox::class,DatabaseInbox::class);
        $this->app->bind(ModelTelemetry::class,DatabaseModelTelemetry::class);
        $this->app->bind(ContextManager::class,DefaultContextManager::class);
        $this->app->bind(KnowledgeAccessPolicy::class,DefaultKnowledgeAccessPolicy::class);
        $this->app->bind(PiiDetector::class,RegexPiiDetector::class);
        $this->app->bind(PromptInjectionDetector::class,HeuristicPromptInjectionDetector::class);

        $this->app->singleton(TelemetryExporter::class,function(){
            $composite=new CompositeTelemetryExporter;
            if(config('agent-fabric.observability.opentelemetry',true))$composite->add(new OpenTelemetryExporter);
            return $composite;
        });

        $this->app->singleton(LaravelAiGateway::class);
        $this->app->bind(ModelGateway::class,ResilientModelGateway::class);
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

        foreach([
            WorkflowEngine::class,CapabilityMatrix::class,AgentVersionFingerprint::class,DatabaseDeploymentManager::class,ReplayService::class,
            MemoryGovernanceService::class,PromptVersionStore::class,DelegationService::class,McpServerPolicy::class,RemoteOperationCoordinator::class,QueueHealthService::class,
        ] as $singleton)$this->app->singleton($singleton);

        $this->app->singleton(PluginContext::class,fn($app)=>new PluginContext($app->make(AgentRegistry::class),$app->make(ConnectorRegistry::class),$app->make(ChannelRegistry::class),$app->make(WorkflowRegistry::class)));
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(AgentFabricManager::class);
    }

    public function boot():void
    {
        $this->publishes([__DIR__.'/../config/agent-fabric.php'=>config_path('agent-fabric.php')],'agent-fabric-config');
        $this->publishes([__DIR__.'/../database/migrations'=>database_path('migrations')],'agent-fabric-migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $agents=$this->app->make(AgentRegistry::class);foreach(config('agent-fabric.agents',[]) as $name=>$class)$agents->register((string)$name,$class);
        $workflows=$this->app->make(WorkflowRegistry::class);foreach(config('agent-fabric.workflows',[]) as $class)$workflows->register($class);
        $connectors=$this->app->make(ConnectorRegistry::class);foreach(config('agent-fabric.connectors',[]) as $class){$instance=$this->app->make($class);$connectors->register($instance);}
        $channels=$this->app->make(ChannelRegistry::class);foreach(config('agent-fabric.channels',[]) as $class){$instance=$this->app->make($class);$channels->register($instance);}
        $plugins=$this->app->make(PluginRegistry::class);foreach(config('agent-fabric.plugins',[]) as $class)$plugins->register($class);
        $reconcilers=$this->app->make(ReconcilerRegistry::class);foreach(config('agent-fabric.remote_operations.reconcilers',[]) as $operation=>$class)$reconcilers->register((string)$operation,$class);
        if($this->app->runningInConsole())$this->commands([DoctorCommand::class,KnowledgeSyncCommand::class,MakeAgentCommand::class,MakeToolCommand::class,MakeWorkflowCommand::class,RunAgentCommand::class,RunWorkflowCommand::class,ReplayRunCommand::class,CapabilitiesCommand::class,ReconcileRemoteOperationsCommand::class,ProcessOutboxCommand::class,HealthCommand::class]);
    }
}
