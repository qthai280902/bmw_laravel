<?php

return [
    'enabled' => env('AI_ASSISTANT_ENABLED', true),
    'provider' => env('AI_ASSISTANT_PROVIDER', 'gemini'),
    'model' => env('AI_ASSISTANT_MODEL'),
    'max_context_products' => env('AI_ASSISTANT_MAX_CONTEXT_PRODUCTS', 30),
    'max_context_articles' => env('AI_ASSISTANT_MAX_CONTEXT_ARTICLES', 5),
    'fallback_message' => 'Hiện tôi chưa thể phản hồi ngay. Quý khách có thể thử lại sau hoặc gửi yêu cầu tư vấn qua form.',
];
