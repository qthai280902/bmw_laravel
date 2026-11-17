<?php

namespace App\Services\Admin;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\Admin\NewAccessoryOrderNotification;
use App\Notifications\Admin\NewAppointmentLeadNotification;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AdminNotificationDispatcher
{
    public const DIGEST_CACHE_PREFIX = 'admin_notifications_digest:';

    /**
     * Track dispatched model object instances within the same lifecycle to stay idempotent.
     *
     * @var array<string, bool>
     */
    private static array $dispatchedInstances = [];

    public function digestCacheKey(int $userId): string
    {
        return self::DIGEST_CACHE_PREFIX.$userId;
    }

    public function forgetDigestCache(int $userId): void
    {
        Cache::forget($this->digestCacheKey($userId));
    }

    public function dispatchNewAppointmentLead(Appointment $appointment): void
    {
        if (! $appointment->exists) {
            return;
        }

        $dedupeKey = 'appointment:'.$appointment->id.':'.spl_object_id($appointment);
        if (isset(self::$dispatchedInstances[$dedupeKey])) {
            return;
        }

        self::$dispatchedInstances[$dedupeKey] = true;

        $this->sendToAdmins(
            new NewAppointmentLeadNotification($appointment),
            'appointment_lead',
            (int) $appointment->id
        );
    }

    public function dispatchNewAccessoryOrder(AccessoryOrder $order): void
    {
        if (! $order->exists) {
            return;
        }

        $dedupeKey = 'accessory_order:'.$order->id.':'.spl_object_id($order);
        if (isset(self::$dispatchedInstances[$dedupeKey])) {
            return;
        }

        self::$dispatchedInstances[$dedupeKey] = true;

        $this->sendToAdmins(
            new NewAccessoryOrderNotification($order),
            'accessory_order',
            (int) $order->id
        );
    }

    private function sendToAdmins(BaseNotification $notification, string $entityType, int $entityId): void
    {
        try {
            $admins = User::query()
                ->admins()
                ->get()
                ->filter(fn (User $user): bool => $user->isAdmin())
                ->values();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, $notification);

            foreach ($admins as $admin) {
                $this->forgetDigestCache((int) $admin->id);
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to dispatch admin database notification.', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
