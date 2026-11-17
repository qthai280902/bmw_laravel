<?php

namespace App\Console\Commands;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PruneAiConversationsCommand extends Command
{
    protected $signature = 'ai:prune-conversations {--days=60 : Số ngày giữ lại lịch sử chat vãng lai chưa đặt lịch}';

    protected $description = 'Dọn dẹp các phiên hội thoại AI vãng lai quá hạn nhưng bảo toàn vĩnh viễn hội thoại gắn với Lead đặt lịch CRM.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $linkedVisitorIdsQuery = Appointment::query()
            ->whereNotNull('ai_visitor_id')
            ->select('ai_visitor_id');

        $prunableQuery = AiChatSession::query()
            ->where('updated_at', '<', $cutoff)
            ->where(function (Builder $query): void {
                $query->whereNull('linked_source_type')
                    ->orWhere('linked_source_type', '!=', 'appointment');
            })
            ->whereDoesntHave('appointments')
            ->where(function (Builder $query) use ($linkedVisitorIdsQuery): void {
                $query->whereNull('visitor_id')
                    ->orWhereNotIn('visitor_id', $linkedVisitorIdsQuery);
            });

        $sessionIdsToDelete = (clone $prunableQuery)->pluck('id');
        $deletedSessions = 0;
        $deletedMessages = 0;

        if ($sessionIdsToDelete->isNotEmpty()) {
            DB::transaction(function () use ($sessionIdsToDelete, &$deletedMessages, &$deletedSessions): void {
                $deletedMessages = AiChatMessage::query()
                    ->whereIn('ai_chat_session_id', $sessionIdsToDelete)
                    ->delete();

                $deletedSessions = AiChatSession::query()
                    ->whereIn('id', $sessionIdsToDelete)
                    ->delete();
            });
        }

        $preservedCrmCount = AiChatSession::query()
            ->where(function (Builder $query) use ($linkedVisitorIdsQuery): void {
                $query->where('linked_source_type', 'appointment')
                    ->orWhereHas('appointments')
                    ->orWhereIn('visitor_id', $linkedVisitorIdsQuery);
            })
            ->count();

        $this->info("Đã dọn dẹp {$deletedSessions} phiên hội thoại vãng lai quá {$days} ngày ({$deletedMessages} tin nhắn). Bảo toàn {$preservedCrmCount} phiên hội thoại gắn với Lead CRM.");

        return self::SUCCESS;
    }
}
