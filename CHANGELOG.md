# Changelog

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
