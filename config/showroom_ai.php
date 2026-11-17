<?php

return [
    'enabled' => env('AI_ASSISTANT_ENABLED', true),
    'provider' => env('AI_ASSISTANT_PROVIDER', 'gemini'),
    'model' => env('AI_ASSISTANT_MODEL'),
    'max_context_products' => env('AI_ASSISTANT_MAX_CONTEXT_PRODUCTS', 30),
    'max_context_articles' => env('AI_ASSISTANT_MAX_CONTEXT_ARTICLES', 5),
    'max_history_messages' => (int) env('AI_ASSISTANT_MAX_HISTORY_MESSAGES', 14),
    'history_ttl_hours' => (int) env('AI_ASSISTANT_HISTORY_TTL_HOURS', 1440),
    'timeout_seconds' => (int) env('AI_ASSISTANT_TIMEOUT_SECONDS', 30),
    'gemini_keys' => [
        'primary' => env('GEMINI_API_KEY'),
        'additional' => env('GEMINI_API_KEYS'),
    ],
    'gemini_key_cooldown_seconds' => env('GEMINI_KEY_COOLDOWN_SECONDS', 120),
    'gemini_key_rotation' => env('GEMINI_KEY_ROTATION', 'round_robin'),
    'embeddings' => [
        'enabled' => filter_var(env('AI_EMBEDDINGS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'provider' => 'cloudflare',
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'accounts_list' => env('CLOUDFLARE_AI_ACCOUNTS'),
        'numbered_accounts' => [
            [
                'account_id' => env('CLOUDFLARE_ACCOUNT_ID_1'),
                'api_token' => env('CLOUDFLARE_API_TOKEN_1'),
            ],
            [
                'account_id' => env('CLOUDFLARE_ACCOUNT_ID_2'),
                'api_token' => env('CLOUDFLARE_API_TOKEN_2'),
            ],
        ],
        'model' => env('CLOUDFLARE_EMBEDDING_MODEL', '@cf/google/embeddinggemma-2'),
        'dimensions' => (int) env('CLOUDFLARE_EMBEDDING_DIMENSIONS', 768),
        'timeout_seconds' => (int) env('CLOUDFLARE_EMBEDDING_TIMEOUT_SECONDS', 8),
        'cooldown_seconds' => (int) env('CLOUDFLARE_EMBEDDING_COOLDOWN_SECONDS', 120),
        'query_cache_ttl_seconds' => (int) env('AI_EMBEDDING_QUERY_CACHE_TTL_SECONDS', 900),
        'semantic_weight' => (int) env('AI_EMBEDDING_SEMANTIC_WEIGHT', 900),
        'min_similarity' => (float) env('AI_EMBEDDING_MIN_SIMILARITY', 0.42),
        'knowledge_embeddings_path' => storage_path('app/private/ai_knowledge_embeddings.json'),
    ],
    'fallback_message' => 'Hiện tôi chưa thể phản hồi ngay. Quý khách có thể thử lại sau hoặc gửi yêu cầu tư vấn qua form.',
    'rate_limit_fallback_message' => 'Hiện trợ lý AI đang nhận nhiều yêu cầu. Quý khách vui lòng thử lại sau ít phút hoặc gửi yêu cầu tư vấn qua form.',
    'structured_recommendations_enabled' => filter_var(env('AI_STRUCTURED_RECOMMENDATIONS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'lead_classification_enabled' => filter_var(env('AI_LEAD_CLASSIFICATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'article_editorial_enabled' => filter_var(env('AI_ARTICLE_EDITORIAL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
