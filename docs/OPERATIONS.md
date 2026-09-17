# Operations

## Health

```bash
php artisan agent-fabric:doctor
php artisan agent-fabric:health
```

The health command reports queue/reliability state. The doctor checks required tables, configured models, embedding settings, and protocol hardening flags.

## Outbox

```bash
php artisan agent-fabric:outbox:work
```

Run the outbox processor under a supervised queue/worker environment. Consumers should use the inbox primitive when a message may be delivered more than once.

## Reconciliation

```bash
php artisan agent-fabric:remote-operations:reconcile
```

Register reconcilers for irreversible providers. Alert on ambiguous operations that exceed the expected reconciliation window rather than issuing blind retries.

## Recommended queue separation

Use separate workers for interactive agents, tools/workflows, embeddings/indexing, evaluations, outbox delivery, and reconciliation. This prevents large indexing or evaluation jobs from starving user-facing conversations.

## OpenTelemetry

Set `observability.opentelemetry.enabled` to true and install an OpenTelemetry API/SDK in the host application. Agent Fabric keeps OpenTelemetry optional and continues persisting its database trace when no exporter is present.

## Database portability

CI exercises SQLite, MySQL 8, and PostgreSQL 16. Production installations should still load-test the exact database version and isolation level used by the application because lock scheduling differs between engines.

## Long-running workers

Agent context is immutable and per-run state is persisted rather than kept in mutable service-provider singletons. When using Octane/RoadRunner/Swoole, do not register application-specific tools that retain request-specific mutable state in singletons.
