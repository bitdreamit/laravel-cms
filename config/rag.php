<?php

return [
    'enabled' => env('RAG_ENABLED', true),
    // Default to 'json' for broad compatibility (MySQL + SQLite).
    // Set to 'pgvector' in .env when using PostgreSQL for vector search at scale.
    'vector_store' => env('RAG_VECTOR_STORE', 'json'),

    'pgvector' => [
        'connection' => env('RAG_DB_CONNECTION', 'pgsql'),
        'index_type' => env('RAG_INDEX_TYPE', 'hnsw'), // hnsw | ivfflat
        'index_params' => [
            'm' => 16,
            'ef_construction' => 64,
        ],
    ],

    'chunking' => [
        'size' => (int) env('RAG_CHUNK_SIZE', 500),
        'overlap' => (int) env('RAG_CHUNK_OVERLAP', 50),
        'split_on' => env('RAG_CHUNK_SPLIT', 'sentence'), // sentence | paragraph | token
    ],

    'retrieval' => [
        'top_k' => (int) env('RAG_TOP_K', 5),
        'min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.7),
    ],

    'generation' => [
        'system_prompt' => env('RAG_SYSTEM_PROMPT', 'You are a helpful assistant. Answer the user\'s question using only the provided context. If the context does not contain the answer, say "I don\'t have enough information to answer that." Always cite sources.'),
        'max_tokens' => (int) env('RAG_MAX_TOKENS', 2000),
        'temperature' => (float) env('RAG_TEMPERATURE', 0.3),
    ],

    'caching' => [
        'query_cache_ttl_minutes' => env('RAG_QUERY_CACHE_TTL', 1440), // 24h for anonymous
        'authenticated_query_cache_ttl_minutes' => 0, // 0 = no cache for logged-in
    ],

    'rate_limits' => [
        'anonymous_per_minute' => env('RAG_ANON_PER_MIN', 5),
        'authenticated_per_minute' => env('RAG_AUTH_PER_MIN', 30),
        'per_tenant_per_hour' => env('RAG_TENANT_PER_HOUR', 200),
    ],

    'embedding_batch_size' => (int) env('RAG_EMBEDDING_BATCH', 100),

    'public_widget' => [
        'enabled_per_tenant_default' => env('RAG_PUBLIC_WIDGET_DEFAULT', false),
        'max_conversation_history' => 5,
    ],
];
