<?php

return [
    'agents' => [],
    'workflows' => [],
    'connectors' => [],
    'channels' => [],
    'plugins' => [],

    'runtime' => [
        'max_steps' => (int) env('AGENT_FABRIC_MAX_STEPS', 20),
        'timeout' => (int) env('AGENT_FABRIC_TIMEOUT', 120),
        'lease_seconds' => (int) env('AGENT_FABRIC_RUN_LEASE_SECONDS', 90),
        'queue' => env('AGENT_FABRIC_QUEUE', 'ai-interactive'),
        'persist_steps' => true,
    ],

    'routing' => [
        'strategy' => env('AGENT_FABRIC_ROUTING', 'balanced'),
        'live_metrics_weight' => (float) env('AGENT_FABRIC_LIVE_METRICS_WEIGHT', 0.25),
        'weights' => [
            'quality' => 0.35,
            'tool_accuracy' => 0.20,
            'reliability' => 0.15,
            'latency' => 0.10,
            'cost' => 0.10,
            'historical_eval' => 0.10,
        ],
        'failover' => [
            'enabled' => true,
            'max_candidates' => (int) env('AGENT_FABRIC_FAILOVER_CANDIDATES', 3),
        ],
        'models' => [],
    ],


    'prompt_versions' => [
        'use_active_override' => (bool) env('AGENT_FABRIC_ACTIVE_PROMPT_OVERRIDE', false),
    ],

    'context' => [
        'default_window' => (int) env('AGENT_FABRIC_CONTEXT_WINDOW', 32000),
        'reserve_output_tokens' => (int) env('AGENT_FABRIC_OUTPUT_TOKEN_RESERVE', 4000),
    ],

    'knowledge' => [
        'candidate_limit' => 250,
        'vector_candidate_limit' => 500,
        'default_limit' => 8,
        'chunk_size' => 1200,
        'chunk_overlap' => 150,
        'embedding_provider' => env('AGENT_FABRIC_EMBEDDING_PROVIDER'),
        'embedding_model' => env('AGENT_FABRIC_EMBEDDING_MODEL'),
        'embedding_dimensions' => (int) env('AGENT_FABRIC_EMBEDDING_DIMENSIONS', 1536),
        'embedding_batch_size' => (int) env('AGENT_FABRIC_EMBEDDING_BATCH_SIZE', 64),
        'lifecycle' => [
            'default_status' => 'active',
            'enforce_effective_dates' => true,
        ],
    ],

    'memory' => [
        'enabled' => true,
        'auto_approve' => (bool) env('AGENT_FABRIC_MEMORY_AUTO_APPROVE', false),
        'default_ttl_days' => 90,
    ],

    'budgets' => [
        'max_cost_per_run' => (float) env('AGENT_FABRIC_MAX_COST_PER_RUN', 1.00),
        'max_tool_calls' => (int) env('AGENT_FABRIC_MAX_TOOL_CALLS', 15),
        'max_same_tool_calls' => (int) env('AGENT_FABRIC_MAX_SAME_TOOL_CALLS', 3),
    ],

    'quotas' => [
        'tenant_steps' => ['limit' => (int) env('AGENT_FABRIC_TENANT_STEPS_PER_MINUTE', 1200), 'window_seconds' => 60],
        'agent_steps' => ['limit' => (int) env('AGENT_FABRIC_AGENT_STEPS_PER_MINUTE', 600), 'window_seconds' => 60],
        'model_calls' => ['limit' => (int) env('AGENT_FABRIC_MODEL_CALLS_PER_MINUTE', 600), 'window_seconds' => 60],
        'actor_steps' => ['limit' => (int) env('AGENT_FABRIC_ACTOR_STEPS_PER_MINUTE', 240), 'window_seconds' => 60],
        'tool_calls' => ['limit' => (int) env('AGENT_FABRIC_TOOL_CALLS_PER_MINUTE', 600), 'window_seconds' => 60],
        'mcp_server' => ['limit' => 60, 'window_seconds' => 60],
    ],

    'reliability' => [
        'circuit_breaker' => [
            'failure_threshold' => (int) env('AGENT_FABRIC_BREAKER_FAILURES', 5),
            'cooldown_seconds' => (int) env('AGENT_FABRIC_BREAKER_COOLDOWN', 60),
        ],
        'outbox' => [
            'lease_seconds' => 60,
            'max_attempts' => 10,
        ],
    ],

    'security' => [
        'require_tenant' => true,
        'redact_pii_before_model' => (bool) env('AGENT_FABRIC_REDACT_PII', false),
        'block_high_risk_user_prompt_injection' => (bool) env('AGENT_FABRIC_BLOCK_PROMPT_INJECTION', false),
        'redact_keys' => ['password','password_confirmation','token','secret','api_key','authorization','cookie','otp'],
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
        'outbox' => 'ai-outbox',
        'reconciliation' => 'ai-reconciliation',
    ],

    'governance' => [
        'default_data_classification' => 'internal',
        'enforce_model_region' => true,
        'enforce_zero_retention' => true,
    ],

    'protocols' => [
        'mcp' => ['enabled' => true, 'require_registered_server' => false],
        'a2a' => [
            'enabled' => true,
            'require_delegation_token' => false,
            'delegation_ttl_seconds' => 300,
        ],
    ],

    'workflows_runtime' => [
        'max_steps' => 100,
        'max_attempts' => 3,
        'backoff_seconds' => 2,
        'max_backoff_seconds' => 60,
        'lease_seconds' => 60,
        'allow_parallel' => true,
        'compensation' => true,
    ],

    'observability' => [
        'tracing' => true,
        'opentelemetry' => (bool) env('AGENT_FABRIC_OPENTELEMETRY', true),
        'store_prompt_bodies' => false,
        'store_tool_bodies' => false,
    ],


    'remote_operations' => [
        'reconcilers' => [],
        'reconciliation_queue' => 'ai-reconciliation',
    ],

    'deployment' => [
        'replay_side_effects' => false,
        'shadow_side_effects' => false,
    ],
];
