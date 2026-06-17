<?php

return [
    'enabled' => env('AI_ASSISTANT_ENABLED', true),
    'provider' => env('AI_ASSISTANT_PROVIDER', 'gemini'),
    'model' => env('AI_ASSISTANT_MODEL'),
    'max_context_products' => env('AI_ASSISTANT_MAX_CONTEXT_PRODUCTS', 30),
    'max_context_articles' => env('AI_ASSISTANT_MAX_CONTEXT_ARTICLES', 5),
    'gemini_keys' => [
        'primary' => env('GEMINI_API_KEY'),
        'additional' => env('GEMINI_API_KEYS'),
    ],
    'gemini_key_cooldown_seconds' => env('GEMINI_KEY_COOLDOWN_SECONDS', 120),
    'gemini_key_rotation' => env('GEMINI_KEY_ROTATION', 'round_robin'),
    'fallback_message' => 'Hiện tôi chưa thể phản hồi ngay. Quý khách có thể thử lại sau hoặc gửi yêu cầu tư vấn qua form.',
    'rate_limit_fallback_message' => 'Hiện trợ lý AI đang nhận nhiều yêu cầu. Quý khách vui lòng thử lại sau ít phút hoặc gửi yêu cầu tư vấn qua form.',
];
