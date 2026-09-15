# Security

AI agents are confused deputies unless authorization remains in application code. The model is a planner, not an authority.

## Mandatory rules

1. Never provide a generic "run SQL" tool.
2. Every tool must enforce the active tenant and actor authorization in PHP/domain services.
3. Never let prompt instructions override Laravel policies.
4. Mark irreversible financial/destructive/privileged actions with the correct risk.
5. Treat RAG documents, web pages, remote APIs and tool output as untrusted data.
6. Do not put secrets, credentials, OTPs or password material in knowledge indexes or memory.
7. Use idempotent domain operations for side effects.
8. Treat timeouts after irreversible remote calls as ambiguous until reconciled.
9. Store only redacted tool/audit payloads if they may contain secrets.
10. Run evals containing prompt-injection and cross-tenant attacks before promoting agent changes.

## Threat model

Test at least:

- direct and indirect prompt injection
- cross-tenant retrieval
- cross-tenant run resume
- approval reuse for different tool arguments
- permission escalation through a tool
- malicious remote/MCP tool output
- secret exfiltration
- memory poisoning
- knowledge poisoning
- SSRF
- repeated tool loops
- duplicate financial operations
- ambiguous provider responses
- denial-of-wallet / token exhaustion
- oversized tool arguments

## Approval integrity

An approval is checked against:

- approval status
- run id
- tenant id
- tool name
- canonicalized tool arguments

Changing any of those values invalidates the approval.

## Production recommendation

For money movement, refunds, purchases, bookings, provisioning or similar irreversible remote actions, the tool should call a durable domain operation with its own idempotency/reconciliation protocol. Agent Fabric protects the orchestration boundary but cannot turn a non-idempotent third-party API into an exactly-once system.
