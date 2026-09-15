# Laravel Agent Fabric

Production AI agent operating system for Laravel applications.

Agent Fabric sits **above** the official `laravel/ai` SDK. The SDK handles provider/model access; Agent Fabric handles the business-runtime concerns that make an AI agent safe and useful in production: tenant isolation, knowledge, model routing, tools, authorization, approvals, idempotency, durable runs, memory, verification, budgets, evaluations, feedback, and auditability.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- `laravel/ai ^0.11`
- mbstring
- A database supported by Laravel
- Queue workers recommended for production

## Install

```bash
composer require evolvex/laravel-agent-fabric
php artisan vendor:publish --tag=agent-fabric-config
php artisan migrate
```

Configure at least one text model in `config/agent-fabric.php`:

```php
'agents' => [
    'support' => App\Ai\Agents\SupportAgent::class,
],

'routing' => [
    'models' => [
        'primary' => [
            'provider' => env('AI_PRIMARY_PROVIDER'),
            'model' => env('AI_PRIMARY_MODEL'),
            'capabilities' => ['reasoning', 'structured_output'],
            'quality' => .90,
            'tool_accuracy' => .90,
            'reliability' => .90,
            'latency' => .70,
            'cost' => .60,
            'historical_eval' => .80,
        ],
    ],
],
```

Provider credentials remain configured through the official Laravel AI SDK.

## Core idea: do not "train on the database"

Agent Fabric separates four concerns:

1. **Knowledge / RAG** for relatively stable content such as policies, documentation, products and help-center content.
2. **Runtime tools** for live or sensitive state such as orders, balances, inventory, payments and bookings.
3. **Instructions / examples / training profiles** for behavior.
4. **Fine tuning** as an optional provider adapter for narrow stable behaviors, not as a replacement for live data access.

This avoids stale balances, stale stock, secret leakage, and the false assumption that a fine-tuned model is a database.

## Create an agent

```bash
php artisan make:agent-fabric Support
php artisan make:agent-tool FindOrder
```

```php
use Evolvex\AgentFabric\Agents\AgentBlueprint;

final class SupportAgent extends AgentBlueprint
{
    public function name(): string
    {
        return 'support';
    }

    public function goal(): string
    {
        return 'Resolve customer support cases end-to-end.';
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
Never claim an action succeeded without tool evidence.
Ask for clarification when required identifiers are ambiguous.
PROMPT;
    }

    public function tools(): array
    {
        return [FindOrder::class, RefundOrder::class];
    }

    public function knowledgeSources(): array
    {
        return ['support-policies', 'product-catalog'];
    }
}
```

Register it:

```php
'agents' => [
    'support' => App\Ai\Agents\SupportAgent::class,
],
```

## Run it

```php
use Evolvex\AgentFabric\Facades\AgentFabric;

$result = AgentFabric::agent('support')
    ->tenant($tenant->id)
    ->actor($customer->id, $customer::class)
    ->ask('My order 551 has not arrived. Find the problem and resolve it.');

$result->runId;
$result->status;
$result->answer;
$result->evidence;
$result->verification;
```

Every run and step is persisted. A run has a correlation id, model usage, tool execution records, approval records and verification data.

## Tools are the authority boundary

A model never receives database authority merely because it asks for it. Every tool is a PHP class with a schema, risk class, authorization and idempotent execution boundary.

```php
final class FindOrder implements AgentTool
{
    public function name(): string { return 'find_order'; }
    public function description(): string { return 'Read an accessible order.'; }
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
            ? ToolResult::success($order->only(['id', 'status']), evidence: ["order:{$order->id}"])
            : ToolResult::failure('Order not found.');
    }
}
```

Risk classes:

- `Read`
- `Write`
- `External`
- `Destructive`
- `Financial`
- `Privileged`

Financial, destructive and privileged tools require approval by default.

## Approval workflow

When an agent requests a high-risk tool:

```php
$result->status === RunStatus::WaitingForApproval;
$approvalId = $result->metadata['approval_id'];
```

Approve and resume the same durable run:

```php
AgentFabric::approve($approvalId, decidedBy: auth()->id());

$result = AgentFabric::agent('support')
    ->tenant($tenant->id)
    ->actor($customer->id, $customer::class)
    ->resume($runId, $approvalId);
```

Rejection is also resumable:

```php
AgentFabric::reject($approvalId, auth()->id(), 'Refund not permitted yet.');

$result = AgentFabric::agent('support')
    ->tenant($tenant->id)
    ->actor($customer->id, $customer::class)
    ->resume($runId, $approvalId);
```

Approval is cryptographically/canonically bound by the runtime to the same run, tenant, tool name and tool arguments. It cannot be reused to authorize a different tool call.

## Clarification workflow

If the model returns a clarification envelope, the run becomes `waiting_for_user` rather than being discarded:

```php
$result = AgentFabric::agent('support')
    ->tenant($tenant->id)
    ->continue($runId, 'I mean order 551.');
```

The prior model/tool transcript is rebuilt from persisted steps.

## Idempotency and ambiguous operations

Tool execution is protected with a unique idempotency key scoped to the run, tool and canonical arguments. Concurrent workers cannot both claim the same tool call.

A tool may explicitly return:

```php
return ToolResult::ambiguous(
    'Provider timed out after accepting the request.',
    ['provider_reference' => $reference],
);
```

The run becomes `ambiguous`; Agent Fabric does **not** blindly retry an irreversible operation. Integrate your reconciliation or remote-operation subsystem at that boundary.

## Knowledge / RAG

A knowledge source returns explicit `KnowledgeDocument` instances. There is no database-wide automatic ingestion.

```php
use Evolvex\AgentFabric\Knowledge\Sources\EloquentKnowledgeSource;

$source = new EloquentKnowledgeSource(
    sourceName: 'product-catalog',
    modelClass: Product::class,
    tenantColumn: 'tenant_id',
    contentFields: ['name', 'description', 'brand'],
    metadataFields: ['id', 'status'],
    titleField: 'name',
    tenantId: (string) $tenant->id,
);
```

Sync any container-resolvable `KnowledgeSource`:

```bash
php artisan agent-fabric:knowledge:sync 'App\Ai\Knowledge\ProductKnowledgeSource'
```

The core database retriever performs portable hybrid scoring (embedding cosine + lexical overlap) on a bounded candidate set and always applies a tenant filter. For very large corpora, implement `Retriever` using pgvector, Qdrant, Pinecone, OpenSearch or a provider vector store; the runtime does not change.

## Memory

The built-in store supports working concepts for:

- conversation
- episodic
- semantic
- procedural

Memory is tenant and actor scoped, has confidence and optional expiry, and is **not** automatically populated from every message. The model may propose explicit memory entries in a final envelope; applications should add policies if they permit this capability.

## Model routing

`WeightedModelRouter` filters configured models by required capabilities and ranks compatible candidates using configurable weights:

- quality
- tool accuracy
- reliability
- latency
- cost
- historical evaluation score

Provider/model names are not hard-coded; this allows official Laravel AI providers and OpenAI-compatible endpoints without changing Agent Fabric.

## Skills

Skills group instructions, tools, knowledge sources and policies:

```php
final class BillingSkill extends Skill
{
    public function name(): string { return 'billing'; }
    public function tools(): array { return [FindInvoice::class, RefundOrder::class]; }
    public function knowledgeSources(): array { return ['billing-policy']; }
}
```

Then:

```php
public function skills(): array
{
    return [BillingSkill::class];
}
```

## Remote / MCP-style tools

`RemoteAgentTool` bridges any `RemoteToolClient` implementation into the same governed tool runtime. Implement `RemoteToolClient` for your MCP or internal tool transport and you retain authorization, approval, idempotency, tenant context and audit behavior.

## Verification

The default verifier refuses to mark an action/result claim verified when tools were used but no tool evidence exists. `Verifier` is a contract: replace it with business invariants, independent-model verification, schema checks or domain-specific factual validators.

Result states include:

- verified
- partially verified
- unverified
- insufficient data
- conflicting data

## Evaluations

```php
$cases = [
    new EvalCase(
        name: 'late order lookup',
        input: 'Where is order 551?',
        tenantId: 'tenant-1',
        expectations: [
            'status' => 'completed',
            'tool_used' => 'find_order',
            'answer_contains' => '551',
        ],
    ),
];

$results = app(EvalRunner::class)->run('support', $cases);
```

Evaluation results can be turned into a release gate or persisted into the included evaluation tables.

## Feedback

```php
AgentFabric::feedback(
    runId: $runId,
    tenantId: $tenant->id,
    rating: -1,
    label: 'wrong_shipping_status',
    reason: 'Carrier data was ignored.',
    correctedAnswer: 'The order is delayed at the carrier hub.',
);
```

Feedback is dataset material; it is deliberately **not** automatically used to retrain or rewrite prompts in production.

## Training profiles

`TrainingProfile` is a declarative representation of what a business means by “train this agent”:

```php
$profile = TrainingProfile::make('sales')
    ->role('Sales agent')
    ->goal('Qualify leads and schedule calls')
    ->learnFrom([Product::class, Faq::class])
    ->allowActions([CreateLead::class, ScheduleCall::class])
    ->never([ChangePrice::class])
    ->languages(['ar', 'en']);
```

It keeps knowledge, permitted actions and behavior separate from fine tuning.

## Fine tuning

Provider-specific fine tuning is deliberately behind `FineTuneProvider`. The core package does not pretend that every model/provider exposes the same fine-tuning lifecycle. Implement an adapter for the provider you actually use and run its data through your eval/release process.

## Security model

Agent Fabric assumes all model output, retrieved content and remote tool output is untrusted.

Controls included in the core:

- explicit tenant context
- tenant-scoped knowledge, memory, approvals, runs and tool executions
- no arbitrary SQL execution
- JSON-like tool input schemas
- tool authorization contract
- high-risk approval boundary
- approval binding to exact call
- idempotent tool claims
- ambiguous operation state
- prompt-injection boundary language
- secret redaction utility
- field allow/deny/redaction policy
- SSRF URL guard
- step/tool/cost limits
- loop detection
- persistent execution trail

Read `docs/SECURITY.md` before allowing destructive or financial tools.

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

`RunAgentJob` is included for async execution.

## Operations

```bash
php artisan agent-fabric:doctor
php artisan agent-fabric:run support "Where is order 551?" --tenant=tenant-1 --actor=customer-8
```

`doctor` checks database connectivity, required tables and configured model profiles.

## Testing

After Composer dependencies are installed:

```bash
composer test
composer lint
composer analyse
```

A dependency-free smoke test for the pure core components is also included:

```bash
php scripts/smoke.php
```

## What the core intentionally does not fake

There is no honest way for a generic package to guarantee “correct answers from every AI model”. Agent Fabric instead makes correctness measurable and enforceable through evidence, tools, policies, verification and evals.

Provider-specific fine tuning, enterprise vector engines, MCP transports and domain invariants are extension contracts because their semantics differ. The core supplies the runtime boundaries they plug into.

See `docs/ARCHITECTURE.md` for the execution model and `examples/` for a complete minimal agent/tool setup.
