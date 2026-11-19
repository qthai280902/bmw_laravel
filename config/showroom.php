<?php

return [
    'concurrency_driver' => env('SHOWROOM_CONCURRENCY_DRIVER', 'process'),
    'ai_chat_rate_per_minute' => (int) env('AI_CHAT_RATE_LIMIT_PER_MINUTE', 15),
    'ai_chat_rate_per_hour' => (int) env('AI_CHAT_RATE_LIMIT_PER_HOUR', 60),
];
