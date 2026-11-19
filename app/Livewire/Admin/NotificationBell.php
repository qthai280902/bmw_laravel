<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Admin\AdminNotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Throwable;

class NotificationBell extends Component
{
    use AuthorizesAdminLivewire;

    public string $filter = 'all';

    public function mount(): void
    {
        $this->authorizeCrm();
    }

    public function refreshDigest(): void
    {
        $this->authorizeCrm();
    }

    public function setFilter(string $filter): void
    {
        $this->authorizeCrm();

        $this->filter = in_array($filter, ['all', 'unread', 'hot'], true) ? $filter : 'all';
    }

    public function markAsRead(string $notificationId): void
    {
        $this->authorizeCrm();

        /** @var User|null $user */
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $notification = $user->notifications()->whereKey($notificationId)->first();
        if ($notification) {
            $notification->markAsRead();
            app(AdminNotificationDispatcher::class)->forgetDigestCache((int) $user->id);
        }
    }

    public function markAllAsRead(): void
    {
        $this->authorizeCrm();

        /** @var User|null $user */
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
        app(AdminNotificationDispatcher::class)->forgetDigestCache((int) $user->id);
    }

    public function render(): View
    {
        $digest = $this->loadDigest();

        $filtered = collect($digest['items'])
            ->filter(function (array $item): bool {
                return match ($this->filter) {
                    'unread' => (bool) ($item['is_unread'] ?? false),
                    'hot' => ($item['lead_priority'] ?? null) === Appointment::LEAD_PRIORITY_HOT,
                    default => true,
                };
            })
            ->take(10)
            ->values()
            ->all();

        return view('livewire.admin.notification-bell', [
            'unreadCount' => (int) ($digest['unread_count'] ?? 0),
            'hasUnreadHotLead' => (bool) ($digest['has_unread_hot_lead'] ?? false),
            'notifications' => $filtered,
        ]);
    }

    /**
     * Load notification digest using at most 1 SQL query on cache miss and 0 on cache hit.
     *
     * @return array{unread_count: int, has_unread_hot_lead: bool, items: list<array<string, mixed>>}
     */
    private function loadDigest(): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (! $user) {
            return [
                'unread_count' => 0,
                'has_unread_hot_lead' => false,
                'items' => [],
            ];
        }

        $cacheKey = app(AdminNotificationDispatcher::class)->digestCacheKey((int) $user->id);

        try {
            return Cache::remember($cacheKey, 60, function () use ($user): array {
                $records = $user->notifications()
                    ->latest()
                    ->limit(25)
                    ->get();

                $unreadRecords = $records->whereNull('read_at');

                return [
                    'unread_count' => $unreadRecords->count(),
                    'has_unread_hot_lead' => $unreadRecords->contains(
                        fn (DatabaseNotification $item): bool => ($item->data['lead_priority'] ?? null) === Appointment::LEAD_PRIORITY_HOT
                    ),
                    'items' => $records->map(
                        fn (DatabaseNotification $item): array => $this->formatNotification($item)
                    )->values()->all(),
                ];
            });
        } catch (Throwable) {
            return [
                'unread_count' => 0,
                'has_unread_hot_lead' => false,
                'items' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNotification(DatabaseNotification $item): array
    {
        $data = is_array($item->data) ? $item->data : [];
        $kind = (string) ($data['kind'] ?? 'appointment_lead');
        $subtitle = match ($kind) {
            'accessory_order' => 'Đơn phụ kiện #'.($data['order_id'] ?? '').' · SL: '.($data['quantity'] ?? 1).' · '.number_format((int) ($data['total_amount'] ?? 0), 0, ',', '.').' VNĐ',
            'mail_delivery_failure' => 'Người nhận: '.($data['recipient_email'] ?? 'N/A').' · '.($data['error_message'] ?? 'Lỗi gửi email'),
            default => (($data['appointment_type'] ?? 'Lịch hẹn').(! empty($data['appointment_date']) ? ' · '.$data['appointment_date'] : '')),
        };

        return [
            'id' => (string) $item->id,
            'kind' => $kind,
            'is_unread' => $item->read_at === null,
            'created_at_human' => $item->created_at?->diffForHumans() ?? 'Vừa xong',
            'lead_priority' => (string) ($data['lead_priority'] ?? Appointment::LEAD_PRIORITY_NORMAL),
            'has_ai_chat' => (bool) ($data['has_ai_chat'] ?? false),
            'ai_chat_summary' => $data['ai_chat_summary'] ?? null,
            'ai_lead_note' => $data['ai_lead_note'] ?? null,
            'title' => (string) ($data['guest_name'] ?? $data['customer_name'] ?? 'Khách hàng Showroom'),
            'phone' => (string) ($data['guest_phone'] ?? $data['customer_phone'] ?? ''),
            'product_name' => (string) ($data['product_name'] ?? 'Dịch vụ BMW'),
            'subtitle' => $subtitle,
            'action_url' => (string) ($data['action_url'] ?? route('admin.appointments.index', [], false)),
        ];
    }
}
