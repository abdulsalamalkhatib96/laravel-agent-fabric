# Laravel Agent Fabric

**Production AI Agent Application Runtime for Laravel.**

Laravel Agent Fabric sits above the official `laravel/ai` SDK. Laravel AI owns provider/model access; Agent Fabric owns the production concerns that determine whether an AI agent is safe, governable, repeatable, auditable, and useful inside a real business application.

The V2 architecture is intentionally **vendor-neutral** and **domain-extensible**. The core does not hard-code Shopify, Salesforce, WhatsApp, OpenAI, Claude, Gemini, or any other vendor. Instead it exposes stable contracts for capabilities, connectors, channels, workflows, tools, protocols, policies, deployment, knowledge, memory, and observability.

## What V2 includes

- Multi-provider capability-aware model routing over `laravel/ai`
- Model data-governance filters: region, data classification, privacy tier, zero-retention
- Durable agent runs, steps, resume, cancellation, approvals, and user clarification
- Governed tools with authorization, schemas, risk classification, idempotency, simulation, pre/post hooks, ambiguity handling, and evidence
- Typed tool families: query, command, remote operation, workflow, human
- Deterministic workflow/DAG runtime with dependencies, human/approval gates, resume, cancellation, and best-effort compensation
- Connector SDK with explicit resource schemas and a safe query-only Eloquent connector
- Channel SDK and multimodal conversation envelopes
- MCP client/server contracts plus local MCP tool exposure
- A2A client contract plus local agent-to-agent gateway
- Plugin SDK and Domain Pack SDK
- RAG with incremental indexing, embeddings, hybrid retrieval, citations, provenance, source authority, and entity references
- Tenant-scoped entity graph abstraction
- Memory, feedback, evaluation primitives, release gates, replay, shadow comparison, and deployments
- Model/tool/run budgets and loop protection
- Prompt trust boundaries and secret/field/URL safety utilities
- Database-backed traces suitable for OpenTelemetry-style export
- Artisan commands, migrations, examples, tests, and smoke tests

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- `laravel/ai ^0.11`
- `ext-mbstring`
- A Laravel-supported database
- Queue workers recommended in production

## Install

```bash
composer require evolvex/laravel-agent-fabric
php artisan vendor:publish --tag=agent-fabric-config
php artisan migrate
```

Configure models in `config/agent-fabric.php`:

```php
'routing' => [
    'models' => [
        'private-reasoning' => [
            'provider' => env('AI_PRIMARY_PROVIDER'),
            'model' => env('AI_PRIMARY_MODEL'),
            'capabilities' => ['text', 'reasoning', 'tools', 'structured_output'],
            'quality' => .92,
            'tool_accuracy' => .95,
            'reliability' => .95,
            'latency' => .70,
            'cost' => .55,
            'historical_eval' => .90,
            'regions' => ['uae'],
            'allowed_classifications' => ['public', 'internal', 'confidential', 'sensitive'],
            'privacy_tier' => 'private',
            'zero_retention' => true,
            'data_residency' => 'uae',
        ],
    ],
],
```

Provider credentials remain configured through Laravel AI.

## Core principle: do not train on live database state

Agent Fabric separates four concerns:

1. **Knowledge / RAG** for relatively stable content such as policies, product descriptions, documentation, FAQs, and manuals.
2. **Runtime tools/connectors** for authoritative live state such as balances, inventory, orders, payments, bookings, or account status.
3. **Instructions, examples, skills, workflows, and policies** for behavior and business rules.
4. **Fine-tuning adapters** only for stable narrow patterns where a provider actually supports fine tuning.

A wallet balance, stock quantity, order status, or payment state should be read at runtime, not baked into a model.

## Define an agent

```php
use Evolvex\AgentFabric\Agents\AgentBlueprint;

final class SupportAgent extends AgentBlueprint
{
    public function name(): string { return 'support'; }
    public function version(): string { return '2.0.0'; }
    public function goal(): string { return 'Resolve customer support cases end-to-end.'; }

    public function instructions(): string
    {
        return <<<'PROMPT'
Never claim an action succeeded without evidence.
Ask for clarification when required identifiers are ambiguous.
Use deterministic workflows for irreversible business operations.
PROMPT;
    }

    public function tools(): array
    {
        return [FindOrder::class, RefundOrder::class];
    }

    public function workflows(): array
    {
        return [RefundWorkflow::class];
    }

    public function connectors(): array
    {
        return ['commerce'];
    }

    public function requiredCapabilities(): array
    {
        return ['reasoning', 'tools', 'structured_output'];
    }
}
```

Register it:

```php
'agents' => [
    'support' => App\Ai\Agents\SupportAgent::class,
],
```

## Run an agent

```php
use Evolvex\AgentFabric\Facades\AgentFabric;

$result = AgentFabric::agent('support')
    ->tenant($tenant->id)
    ->actor($customer->id, $customer::class)
    ->dataClassification('sensitive')
    ->region('uae')
    ->requireZeroRetention()
    ->ask('Order 551 never arrived. Resolve it.');
```

For replay/shadow/simulation:

```php
$result = AgentFabric::agent('support')
    ->tenant($tenant->id)
    ->actor($customer->id, $customer::class)
    ->simulate()
    ->ask('Refund order 551');
```

Governed command and remote-operation tools are not executed in simulation mode.

## Governed tools

The original `AgentTool` contract remains supported for backward compatibility. New production tools should extend one of the typed bases:

```php
use Evolvex\AgentFabric\Tools\QueryTool;
use Evolvex\AgentFabric\Tools\CommandTool;
use Evolvex\AgentFabric\Tools\RemoteOperationTool;
```

Example:

```php
final class FindOrder extends QueryTool
{
    public function name(): string { return 'find_order'; }
    public function description(): string { return 'Read one accessible order.'; }
    public function risk(): ToolRisk { return ToolRisk::Read; }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['order_id' => ['type' => 'string']],
            'required' => ['order_id'],
        ];
    }

    public function execute(AgentContext $context, array $arguments): ToolResult
    {
        $order = Order::query()
            ->where('tenant_id', $context->tenantId)
            ->where('customer_id', $context->actorId)
            ->find($arguments['order_id']);

        return $order
            ? ToolResult::success($order->only(['id','status']), evidence: ["order:{$order->id}"])
            : ToolResult::failure('Order not found.');
    }
}
```

`RemoteOperationTool` defaults to no blind automatic retry and explicitly requires reconciliation when the result is ambiguous.

## Human approval

Financial, destructive, and privileged tools require approval by default.

```php
if ($result->status === RunStatus::WaitingForApproval) {
    $approvalId = $result->metadata['approval_id'];

    AgentFabric::approve($approvalId, auth()->id());

    $result = AgentFabric::agent('support')
        ->tenant($tenant->id)
        ->actor($customer->id, $customer::class)
        ->resume($result->runId, $approvalId);
}
```

Approval is bound to the exact run, tenant, tool, and canonical arguments. It cannot be reused for a different call.

## Deterministic workflows

Do not use an LLM loop as the transaction coordinator for sensitive operations. Put business-critical sequences in deterministic workflows.

```php
final class RefundWorkflow implements Workflow
{
    public function name(): string { return 'refund-order'; }
    public function version(): string { return '1.0.0'; }

    public function definition(): WorkflowDefinition
    {
        return (new WorkflowDefinition($this->name(), $this->version()))
            ->step('validate', ValidateRefund::class)
            ->approval('finance-approval', ['validate'])
            ->step('provider-refund', ExecuteRefund::class, ['finance-approval'], compensation: ReconcileRefund::class)
            ->step('update-order', MarkOrderRefunded::class, ['provider-refund'])
            ->verify('verify-state', VerifyRefundState::class, ['update-order']);
    }
}
```

Run it:

```php
$result = AgentFabric::workflow(
    'refund-order',
    new AgentContext($tenant->id, $customer->id, $customer::class),
    ['order_id' => '551']
);
```

The workflow engine persists runs and steps, enforces dependencies, pauses at human/approval gates, supports resume/cancel, and compensates completed steps in reverse order when configured.

## Connector SDK

A connector exposes a stable business integration contract without granting arbitrary database or HTTP access to the model.

```php
interface Connector
{
    public function name(): string;
    public function schema(): ConnectorSchema;
    public function query(AgentContext $context, ConnectorQuery $query): ConnectorResult;
    public function execute(AgentContext $context, ConnectorAction $action): ConnectorResult;
}
```

The built-in `EloquentConnector` is intentionally query-only. Writes should be explicit governed tools or workflows.

```php
$connector = new EloquentConnector('commerce', [
    'orders' => [
        'model' => Order::class,
        'tenant_column' => 'tenant_id',
        'fields' => ['id','customer_id','status','total','created_at'],
    ],
]);
```

This prevents the anti-pattern of letting an LLM generate arbitrary SQL.

## Plugin and Domain Pack SDK

Plugins can register agents, connectors, channels, and workflows:

```php
final class EcommercePack extends AbstractDomainPack
{
    public function name(): string { return 'ecommerce'; }
    public function domain(): string { return 'ecommerce'; }

    public function register(PluginContext $context): void
    {
        $context->workflows->register(RefundWorkflow::class);
        $context->agents->register('support', SupportAgent::class);
    }
}
```

This is the intended way to support ecommerce, CRM, booking, HR, finance, CMS, real estate, or other verticals without turning the core package into a vendor-specific monolith.

## Channels and multimodal input

A channel normalizes provider-specific payloads into `ConversationEnvelope`:

```php
$envelope = new ConversationEnvelope(
    channel: ChannelType::WhatsApp,
    tenantId: $tenant->id,
    parts: [
        InputPart::text('Inspect this damage'),
        InputPart::image($imageUrl, 'image/jpeg'),
    ],
    actorId: $customer->id,
    actorType: $customer::class,
);

$result = AgentFabric::fromEnvelope('claims', $envelope);
```

Adapters for WhatsApp, Slack, Teams, Telegram, voice, or proprietary channels should live in plugins/packs.

## MCP

Use `McpClient` to connect to remote MCP transports and `McpAgentTool` to expose an MCP tool through Agent Fabric governance.

For local exposure, `LocalMcpServer` maps a `ToolRegistry` to MCP-style tool discovery/calls while preserving ToolExecutor authorization, approvals, idempotency, and evidence.

Transport/authentication remain adapter concerns. The core does not pretend every MCP deployment uses the same HTTP/OAuth topology.

## A2A

`A2AClient` and `A2AAgentTool` allow delegation to remote agents. `LocalA2AGateway` exposes local agent cards and delegates tasks to another Agent Fabric agent with explicit tenant/actor context.

MCP is for tool/resource interoperability; A2A is for agent-to-agent delegation. They are separate boundaries.

## Knowledge, provenance, and entity references

Knowledge documents may include authority and provenance:

```php
new KnowledgeDocument(
    sourceType: 'policy',
    sourceKey: 'refund-v4',
    title: 'Refund Policy',
    content: $text,
    tenantId: $tenant->id,
    provenance: new KnowledgeProvenance(
        source: 'official-policy',
        sourceId: 'v4',
        authority: 1.0,
        observedAt: new DateTimeImmutable(),
    ),
    entities: [new EntityReference('policy', 'refund', $tenant->id)],
);
```

The portable retriever scores semantic similarity, lexical relevance, and source authority. Large installations should bind `Retriever` to pgvector, Qdrant, Pinecone, OpenSearch, or another native vector engine.

The built-in entity store provides tenant-scoped entity/relation primitives without forcing a graph database.

## Trust boundary / prompt injection defense

Agent Fabric treats retrieved content, web content, remote tool responses, and user content as data—not as system instructions.

`ContextItem` + `PromptBoundary` preserve source/trust metadata. Tools still enforce authorization in PHP even if the model is manipulated.

Prompt injection cannot be solved with one regex. The security model is layered:

- least-agency tool catalogs
- tenant isolation
- explicit field/resource allowlists
- policy engine
- delegated identity
- approvals
- idempotency
- SSRF guard
- secret redaction
- evidence verification
- trusted/untrusted context boundaries
- simulation/replay

## Deployment, replay, shadow, and release gates

Register agent versions using a deterministic fingerprint, then deploy active/canary/shadow versions through `DatabaseDeploymentManager`.

```php
$gate = new ReleaseGate(
    minimums: ['task_success' => .97, 'citation_accuracy' => .98],
    maximums: ['unsafe_action_rate' => 0.0, 'p95_latency_ms' => 5000],
);

$gateResult = $gate->evaluate($metrics);

AgentFabric::deploy('support', '2.1.0', DeploymentMode::Canary, 5, $gateResult);
```

Replay an existing run with side effects disabled:

```bash
php artisan agent-fabric:replay <run-id>
```

`ShadowComparator` compares status, answer, and tool trajectory between primary and candidate results. For high-scale production shadow execution, connect this boundary to your dedicated shadow runtime infrastructure.

## Model governance

Model routing can reject a provider/model before quality scoring if it violates data policy:

```php
AgentFabric::agent('support')
    ->tenant($tenant->id)
    ->dataClassification('sensitive')
    ->region('uae')
    ->requireZeroRetention()
    ->ask($message);
```

A model profile may declare:

- regions
- allowed data classifications
- privacy tier
- zero-retention support
- data residency
- capabilities
- quality/tool accuracy/reliability/cost/latency/eval scores

## Observability

The runtime records spans for:

- agent lifecycle
- retrieval
- model calls
- tool calls
- failures

`TraceRecorder` is a contract. The default database implementation writes `ai_trace_spans`; replace it with an OpenTelemetry exporter, Datadog, Sentry, or your tracing stack without changing agents.

## Evaluation

The package includes evaluation primitives and deployment release gates. Evaluate business outcomes, not only semantic answer similarity:

- task completion
- correct tool selection
- tool argument accuracy
- policy compliance
- authorization violations
- citation correctness
- final business state
- unsafe action rate
- latency
- cost

Do not use an LLM judge as the only release criterion.

## Artisan commands

```bash
php artisan agent-fabric:doctor
php artisan agent-fabric:capabilities
php artisan agent-fabric:knowledge:sync 'App\Ai\Knowledge\ProductKnowledgeSource'
php artisan agent-fabric:run support "Where is order 551?" --tenant=tenant-1
php artisan agent-fabric:workflow:run refund-order --tenant=tenant-1 --input='{"order_id":"551"}'
php artisan agent-fabric:replay <run-id>
php artisan make:agent-fabric Support
php artisan make:agent-tool FindOrder
php artisan make:agent-workflow RefundOrder
```

## Queues

Recommended queues:

```text
ai-interactive
ai-tools
ai-ingestion
ai-embeddings
ai-evaluations
ai-maintenance
```

Never run bulk embedding ingestion on the same worker pool that serves interactive conversations.

## Architecture boundaries

The core deliberately does **not** implement vendor-specific integrations for every service. Those belong in plugins/domain packs. The core owns the invariants that must stay consistent across all sites:

```text
Agent definition
  -> capability/data-policy routing
  -> knowledge + memory
  -> model decision
  -> governed tool / deterministic workflow
  -> authorization / approval / idempotency
  -> evidence / verification
  -> durable state / traces / evals
```

This is how Agent Fabric can support many different websites without becoming an unmaintainable God Package.

## Testing

```bash
composer test
composer lint
composer analyse
php scripts/smoke.php
```

The dependency-free smoke test covers core parsing, schema validation, security, training profiles, trust boundaries, multimodal envelopes, release gates, and workflow definitions.

See:

- `docs/ARCHITECTURE.md`
- `docs/SECURITY.md`
- `docs/EXTENDING.md`
- `docs/WORKFLOWS.md`
- `docs/PROTOCOLS.md`

## What Agent Fabric does not claim

No generic package can guarantee that every LLM answer is correct or support every external vendor without an adapter. Agent Fabric instead makes model behavior constrained, observable, testable, auditable, and replaceable while keeping business authority in Laravel code.
