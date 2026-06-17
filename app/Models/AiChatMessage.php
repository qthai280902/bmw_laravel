<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    protected $fillable = [
        'ai_chat_session_id',
        'role',
        'content',
        'content_preview',
        'page_url',
        'page_path',
        'referrer',
        'provider',
        'model',
        'response_reason',
        'latency_ms',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }

    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }
}
