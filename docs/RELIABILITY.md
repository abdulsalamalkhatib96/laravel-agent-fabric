# Reliability

Agent Fabric treats model calls and irreversible side effects as different reliability domains.

## Ownership and concurrency

Agent runs, workflow runs, workflow steps, and run-step sequence allocation use `LeaseManager`. A worker must own a lease before mutating durable execution state. Leases expire, can be renewed, and are released explicitly so crashed workers can be recovered without allowing two healthy workers to execute the same critical section concurrently.

## Outbox and inbox

Use `Outbox::add()` inside the same database transaction as the business state change. `agent-fabric:outbox:work` claims pending records with a lease and acknowledges or schedules retry. `Inbox` deduplicates consumer/message pairs for idempotent event handling.

## Remote operations

An HTTP timeout does not prove that a provider failed. `RemoteOperationCoordinator` persists an operation before sending it. Transport uncertainty moves the operation to an ambiguous state and a registered `RemoteOperationReconciler` determines the provider truth later. Durable remote-operation tools therefore do not blindly retry an irreversible request.

## Workflow states

Workflows persist `created`, `running`, `waiting`, `retrying`, `ambiguous`, `compensating`, `compensated`, `completed`, `failed`, and `cancelled`. Retry backoff and attempt limits are persisted, not kept in process memory. Compensation runs completed compensatable steps in reverse order.

## Failure classes

Model failures are classified as timeout, rate-limit, provider/server failure, context overflow, safety refusal, transport failure, or unknown. Only retryable classes are eligible for provider failover. Malformed Agent Fabric envelopes have a bounded repair budget.

## Correctness rules

- Never use a model retry to repair an ambiguous external side effect.
- Never publish a durable business event before its transaction commits.
- Never execute a workflow step without ownership.
- Never treat compensation as successful when no compensation actually ran.
- Keep every retry idempotent or protected by a provider idempotency key.
