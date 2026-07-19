<?php

return [
    'default_provider' => env('AI_PROVIDER', 'openai'),
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o'),
            'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'embedding_dimensions' => (int) env('OPENAI_EMBEDDING_DIMS', 1536),
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 4096),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            // NOTE: Use the latest alias from docs.claude.com/en/api/model-names
            // 'claude-sonnet-4-6' is an alias that always points to the newest Sonnet 4 release.
            // Avoid pinning to dated snapshots like 'claude-sonnet-4-20250514' — they are deprecated.
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
            'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        ],
        'local' => [
            'base_url' => env('LOCAL_AI_URL', 'http://localhost:11434'),
            'model' => env('LOCAL_AI_MODEL', 'llama3'),
        ],
    ],
    'enabled_for_tenants' => env('AI_ENABLED_FOR_TENANTS', true),
    'rate_limit_per_tenant' => env('AI_RATE_LIMIT', 100),
    'prompt_templates_path' => app_path('Domain/Ai/Prompts'),
    'rag' => [
        'enabled' => env('AI_RAG_ENABLED', true),
        // Default to 'json' for broad compatibility (MySQL + SQLite).
        // Set to 'pgvector' in .env when using PostgreSQL for vector search at scale.
        'vector_store' => env('RAG_VECTOR_STORE', 'json'),
        'chunk_size' => (int) env('RAG_CHUNK_SIZE', 500),
        'chunk_overlap' => (int) env('RAG_CHUNK_OVERLAP', 50),
        'top_k' => (int) env('RAG_TOP_K', 5),
        'system_prompt' => 'You are a helpful assistant answering questions based on the provided context. Always cite sources when using information from the context.',
    ],
];
