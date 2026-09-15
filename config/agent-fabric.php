<?php

return [
    // name => AgentBlueprint class. Register dynamically via AgentFabric::registry() if preferred.
    'agents' => [],

    // Extension registries. Keep vendor-specific integrations in plugins/packs.
    'workflows' => [],
    'connectors' => [],
    'channels' => [],
    'plugins' => [],

    'runtime' => [
        'max_steps' => (int) env('AGENT_FABRIC_MAX_STEPS', 20),
        'timeout' => (int) env('AGENT_FABRIC_TIMEOUT', 120),
        'queue' => env('AGENT_FABRIC_QUEUE', 'ai-interactive'),
        'persist_steps' => true,
    ],

    'routing' => [
        'strategy' => env('AGENT_FABRIC_ROUTING', 'balanced'),
        'weights' => [
            'quality' => 0.35, 'tool_accuracy' => 0.20, 'reliability' => 0.15,
            'latency' => 0.10, 'cost' => 0.10, 'historical_eval' => 0.10,
        ],
        'models' => [],
    ],

    'knowledge' => [
        'candidate_limit' => 250,
        'default_limit' => 8,
        'chunk_size' => 1200,
        'chunk_overlap' => 150,
        'embedding_provider' => env('AGENT_FABRIC_EMBEDDING_PROVIDER'),
        'embedding_model' => env('AGENT_FABRIC_EMBEDDING_MODEL'),
        'embedding_dimensions' => (int) env('AGENT_FABRIC_EMBEDDING_DIMENSIONS', 1536),
        'embedding_batch_size' => (int) env('AGENT_FABRIC_EMBEDDING_BATCH_SIZE', 64),
    ],

    'memory' => [
        'enabled' => true,
        'default_ttl_days' => 90,
    ],

    'budgets' => [
        'max_cost_per_run' => (float) env('AGENT_FABRIC_MAX_COST_PER_RUN', 1.00),
        'max_tool_calls' => (int) env('AGENT_FABRIC_MAX_TOOL_CALLS', 15),
        'max_same_tool_calls' => (int) env('AGENT_FABRIC_MAX_SAME_TOOL_CALLS', 3),
    ],

    'security' => [
        'require_tenant' => true,
        'redact_keys' => ['password', 'password_confirmation', 'token', 'secret', 'api_key', 'authorization', 'cookie', 'otp'],
        'redact_patterns' => [
            '/Bearer\s+[A-Za-z0-9._\-]+/i',
            '/sk-[A-Za-z0-9_-]{16,}/',
        ],
    ],

    'verification' => [
        'require_evidence_for_tool_claims' => true,
        'minimum_grounding_score' => 0.50,
        'require_citations_when_knowledge_available' => true,
    ],

    'queues' => [
        'interactive' => 'ai-interactive',
        'tools' => 'ai-tools',
        'ingestion' => 'ai-ingestion',
        'embeddings' => 'ai-embeddings',
        'evaluations' => 'ai-evaluations',
        'maintenance' => 'ai-maintenance',
    ],

    'governance' => [
        'default_data_classification' => 'internal',
        'enforce_model_region' => true,
        'enforce_zero_retention' => true,
    ],

    'protocols' => [
        'mcp' => ['enabled' => true],
        'a2a' => ['enabled' => true],
    ],

    'workflows_runtime' => [
        'max_steps' => 100,
        'allow_parallel' => true,
        'compensation' => true,
    ],

    'observability' => [
        'tracing' => true,
        'store_prompt_bodies' => false,
        'store_tool_bodies' => false,
    ],

    'deployment' => [
        'replay_side_effects' => false,
        'shadow_side_effects' => false,
    ],
];
