# Extending Agent Fabric

## Prefer plugins over core changes

Create a plugin when integrating a vendor or business domain. Do not add vendor-specific behavior to the kernel unless it represents a cross-domain invariant.

## Connector

Implement `Connector` for systems that expose resources/actions. Keep schemas explicit and tenant-aware. Do not expose arbitrary SQL.

## Channel

Implement `ChannelAdapter` to normalize provider payloads into `ConversationEnvelope` and deliver responses.

## Domain Pack

Implement `DomainPack` / extend `AbstractDomainPack` for ecommerce, booking, CRM, HR, finance, CMS, etc. A pack may register agents, workflows, connectors, and channels.

## Model provider

Prefer the official Laravel AI provider layer. Extend `ModelGateway` only when a provider-specific feature cannot be expressed through Laravel AI.

## Vector engine

Bind `Retriever` and `KnowledgeStore` to a native vector backend for large corpora. Preserve tenant filters and source provenance.

## Tracing

Bind `TraceRecorder` to OpenTelemetry/Datadog/Sentry if database spans are not appropriate.
