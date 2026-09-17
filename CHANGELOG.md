# Changelog

## 0.3.0 - 2026-09-17

- Added distributed leases for agent runs, workflow runs/steps, and sequence allocation.
- Added transactional outbox/inbox messaging and durable outbox processing.
- Added persisted remote-operation ambiguity and reconciliation semantics.
- Added circuit breakers, tenant/actor/agent/model/tool/MCP quotas, and cost budgets.
- Added resilient provider failover with failure classification and live model telemetry.
- Added context-window compaction and bounded malformed-envelope recovery.
- Added prompt version registration, activation, and rollback.
- Added richer JSON-schema validation and governed tool output schemas/errors.
- Added fluent tool governance policies and approval thresholds.
- Added PII redaction, prompt-injection inspection, knowledge ACL/lifecycle/effective-date enforcement.
- Added memory-candidate governance, scoped A2A delegation tokens, and MCP fingerprint/allowlist/quota policy.
- Added vector-store contracts and adapters for database/pgvector-style storage, Qdrant, Pinecone, and OpenSearch.
- Added OpenTelemetry export hooks, queue health diagnostics, model observations, and operational commands.
- Hardened deterministic workflows with retry/backoff, delay, ambiguity, cancellation, and compensation states.
- Added concurrency, failure/reconciliation, memory, workflow durability, security, schema, portability, and request-state isolation tests.
- Expanded CI to PHP 8.3/8.4, Laravel 12/13, SQLite, MySQL 8, and PostgreSQL 16.

## 0.2.0 - 2026-09-15

- Added vendor-neutral V2 platform architecture.
- Added capability/data-governance model routing including region, classification, privacy tier, and zero-retention filters.
- Added deterministic workflow/DAG runtime with persisted steps, human/approval gates, resume, cancellation, and compensation hooks.
- Added Connector SDK, safe query-only Eloquent connector, callback connector, and connector registry.
- Added Channel SDK and multimodal conversation envelopes.
- Added MCP client/server contracts, local MCP server, MCP governed tool adapter, and callback client.
- Added A2A client contract, local gateway, agent tool adapter, and callback client.
- Added Plugin SDK and Domain Pack SDK.
- Added typed governed tools and simulation-safe command/remote-operation behavior.
- Added knowledge provenance, source authority scoring, entity references, and tenant-scoped entity graph storage.
- Added deployment fingerprints, deployments, canary/shadow modes, release gates, replay service, and shadow comparator.
- Added database tracing and runtime model/retrieval/tool spans.
- Added prompt trust boundaries and additional V2 tests / smoke coverage.

## 0.1.0 - 2026-09-15

- Initial durable agent runtime.
- Multi-model routing over Laravel AI SDK.
- Tenant-safe knowledge, tools, approvals, memory, verification, budgets, feedback, and eval primitives.
