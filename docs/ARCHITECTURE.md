# Architecture

## Planes

```text
Application
  |
  +-- Agent definitions / Skills / Policies
  |
Agent Fabric Runtime
  +-- Durable Run + Step Store
  +-- Model Router -> Laravel AI Gateway
  +-- Knowledge Retriever -> Embeddings / Knowledge Store
  +-- Tool Executor -> Authorization -> Approval -> Idempotency
  +-- Memory
  +-- Budget / Loop Controls
  +-- Verification / Evidence
  +-- Eval / Feedback / Audit
  |
Laravel AI SDK
  |
AI providers / OpenAI-compatible endpoints
```

## Runtime protocol

Agent Fabric uses a provider-independent JSON control envelope:

```json
{"type":"tool","tool":"find_order","arguments":{"order_id":"551"}}
```

or:

```json
{"type":"final","answer":"...","memory":[]}
```

or `clarify` / `escalate`. This keeps the orchestration layer independent from provider-specific tool APIs. `ModelGateway` can be replaced with a provider-native structured-output implementation without changing the runtime.

## Execution lifecycle

```text
create run
  -> enforce start policies
  -> retrieve tenant-scoped knowledge
  -> recall memory
  -> route model by capabilities/score
  -> model call
  -> parse envelope
      -> tool: schema -> authorize -> approval? -> idempotent claim -> execute
      -> clarify: persist waiting_for_user
      -> escalate: return human handoff
      -> final: verify evidence -> persist memory -> complete
```

Each model/tool/verification operation becomes a persisted step.

## Failure semantics

- transient model failure: gateway/provider failover can be added behind `ModelGateway`
- policy denial: fail closed
- approval required: pause, do not execute
- rejected approval: resume with a rejection observation
- tool failure: feed result back to the agent
- ambiguous irreversible tool result: stop in `ambiguous`; do not blind retry
- max steps/cost/tool calls: stop by budget control
- worker/process loss: persisted run/steps provide the continuation source of truth

## Data freshness

Use RAG for policies/docs/catalog-like knowledge. Use tools for current balances, order state, inventory and other volatile/authoritative data.

## Scaling knowledge search

The included retriever is database-portable and intentionally bounded. It is appropriate for initial deployments and smaller knowledge sets. Large corpora should bind `Retriever` to a native vector implementation. The storage/runtime contracts were designed so that this is a binding change rather than a rewrite of agents.
