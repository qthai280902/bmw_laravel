<?php

return [
    'enabled' => env('AI_ASSISTANT_ENABLED', true),
    'provider' => env('AI_ASSISTANT_PROVIDER', 'gemini'),
    'model' => env('AI_ASSISTANT_MODEL'),
    'max_context_products' => env('AI_ASSISTANT_MAX_CONTEXT_PRODUCTS', 8),
    'max_context_articles' => env('AI_ASSISTANT_MAX_CONTEXT_ARTICLES', 5),
    'fallback_message' => 'Trợ lý AI hiện chưa được cấu hình hoàn chỉnh. Bạn vẫn có thể xem danh mục xe, bài viết hoặc gửi yêu cầu tư vấn qua form.',
];
