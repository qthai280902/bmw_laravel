<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    /**
     * Touches parent AiChatSession updated_at whenever a message changes.
     *
     * @var list<string>
     */
    protected $touches = ['session'];

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
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'meta_data' => AsArrayObject::class,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }

    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }
}
