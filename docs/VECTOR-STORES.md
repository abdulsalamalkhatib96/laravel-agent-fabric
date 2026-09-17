# Vector Stores

`VectorStore` is the stable boundary between Agent Fabric retrieval and a concrete vector engine.

Included adapters:

- Database / pgvector-style record store for small and medium installations.
- Qdrant transport adapter.
- Pinecone transport adapter.
- OpenSearch k-NN transport adapter.

The remote adapters depend on `VectorStoreTransport`, so authentication, retries, HTTP instrumentation, private networking, and provider-specific client libraries remain application concerns rather than being hard-coded into the runtime.

For large corpora, keep authoritative document metadata and ACLs in Agent Fabric while using the external vector engine as a retrieval index. Always re-apply tenant, lifecycle, classification, and ACL policy before retrieved content reaches the model.
