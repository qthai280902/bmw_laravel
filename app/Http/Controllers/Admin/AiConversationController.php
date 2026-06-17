<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessoryOrder;
use App\Models\AiChatSession;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiConversationController extends Controller
{
    public function index(Request $request): View
    {
        $statusOptions = AiChatSession::statuses();

        $query = AiChatSession::query()
            ->with('latestMessage')
            ->withCount('messages')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('display_name', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('visitor_id', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('last_message_preview', 'like', "%{$search}%")
                        ->orWhereHas('messages', function (Builder $query) use ($search): void {
                            $query->where('content', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->string('identity')->toString() === 'linked', function (Builder $query): void {
                $query->whereNotNull('linked_source_type');
            })
            ->when($request->string('identity')->toString() === 'unknown', function (Builder $query): void {
                $query->whereNull('linked_source_type');
            })
            ->when($request->string('interest')->toString() === 'product', function (Builder $query): void {
                $query->whereNotNull('last_intent');
            })
            ->when($request->filled('reason'), function (Builder $query) use ($request): void {
                $reason = $request->string('reason')->toString();

                $query->whereHas('messages', function (Builder $query) use ($reason): void {
                    $query->where('role', 'assistant')->where('response_reason', $reason);
                });
            })
            ->when($request->filled('status') && in_array($request->string('status')->toString(), AiChatSession::statusValues(), true), function (Builder $query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('range'), function (Builder $query) use ($request): void {
                $range = $request->string('range')->toString();

                match ($range) {
                    'today' => $query->whereDate('last_seen_at', today()),
                    '7d' => $query->where('last_seen_at', '>=', now()->subDays(7)),
                    '30d' => $query->where('last_seen_at', '>=', now()->subDays(30)),
                    default => null,
                };
            });

        $sessions = $query
            ->latest('last_seen_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'today' => AiChatSession::query()->whereDate('last_seen_at', today())->count(),
            'linked' => AiChatSession::query()->whereNotNull('linked_source_type')->count(),
            'fallback' => AiChatSession::query()
                ->whereHas('messages', fn (Builder $query): Builder => $query->where('role', 'assistant')->where('response_reason', '!=', 'ok'))
                ->count(),
            'interested' => AiChatSession::query()->whereNotNull('last_intent')->count(),
        ];

        return view('admin.ai-conversations.index', [
            'sessions' => $sessions,
            'stats' => $stats,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function show(AiChatSession $session): View
    {
        $session->load(['messages']);

        return view('admin.ai-conversations.show', [
            'linkedRecord' => $this->linkedRecord($session),
            'session' => $session,
            'statusOptions' => AiChatSession::statuses(),
        ]);
    }

    public function updateStatus(Request $request, AiChatSession $session): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(AiChatSession::statusValues())],
        ]);

        $session->update(['status' => $data['status']]);

        return redirect()
            ->route('admin.ai-conversations.show', $session)
            ->with('success', 'Đã cập nhật trạng thái hội thoại AI.');
    }

    private function linkedRecord(AiChatSession $session): Appointment|AccessoryOrder|null
    {
        return match ($session->linked_source_type) {
            'appointment' => Appointment::query()->with('product')->find($session->linked_source_id),
            'accessory_order' => AccessoryOrder::query()->with('product')->find($session->linked_source_id),
            default => null,
        };
    }
}
