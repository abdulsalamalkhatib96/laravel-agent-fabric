# Architecture

Laravel Agent Fabric V2 is a kernel plus extension ecosystem.

```text
Application / SaaS / Ecommerce / CRM / ERP
                 |
          Agent Fabric API
                 |
 +---------------+----------------+
 |               |                |
Agents        Workflows        Governance
 |               |                |
 +----------- Capability Router --+
                 |
             Laravel AI
                 |
            AI Providers

Side planes:
Connectors | Channels | MCP | A2A | Knowledge | Memory | Evals | Traces | Deployments
```

## Authority model

The model has **decision authority only**, never implicit business authority. Business authority lives in PHP tools, connectors, policies, workflow handlers, delegated identity, and approval rules.

## Volatile state

Live state belongs behind runtime tools/connectors. Stable knowledge belongs in RAG. A model should not be fine-tuned on current balances, stock, or order state.

## Execution

```text
create durable run
 -> policy checks
 -> tenant-scoped retrieval + memory
 -> capability/data-policy model routing
 -> model control envelope
    -> query tool
    -> command tool
    -> remote operation
    -> deterministic workflow
    -> clarification/escalation
 -> evidence verification
 -> persist result + trace + usage
```

## Sensitive workflows

Financial/destructive sequences should move from free-form agent loops to `WorkflowDefinition`. Workflows persist dependencies, pause at human gates, resume, cancel, and compensate completed steps best-effort. Remote ambiguity should be reconciled rather than blindly retried.

## Extension model

Vendor integrations belong in plugins/domain packs. A plugin can register agents, connectors, channels, and workflows through `PluginContext`. This keeps core contracts stable while allowing vertical-specific packages.

## Interoperability

- MCP: tools/resources
- A2A: agent delegation
- Connector SDK: application/business systems
- Channel SDK: inbound/outbound conversation surfaces

Transport and vendor authentication are adapter responsibilities.
