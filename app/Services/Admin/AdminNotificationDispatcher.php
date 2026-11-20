<?php

namespace App\Services\Admin;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\Admin\LeadAssignedNotification;
use App\Notifications\Admin\MailDeliveryFailureNotification;
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

    /**
     * Resolve Showroom admin notification email list supporting comma-separated values.
     *
     * @return list<string>
     */
    public static function showroomAdminEmails(): array
    {
        $parseEmails = static function (mixed $raw): array {
            $parts = is_array($raw)
                ? $raw
                : (is_string($raw) ? explode(',', $raw) : []);

            return array_values(array_unique(array_filter(array_map(
                static fn (mixed $item): string => mb_strtolower(trim((string) $item)),
                $parts
            ), static fn (string $email): bool => $email !== '')));
        };

        $fromSingle = $parseEmails(config('mail.showroom_admin', 'advisor@bmw.com'));
        $fromArray = $parseEmails(config('mail.showroom_admins', []));

        if ($fromSingle === $fromArray && $fromArray !== []) {
            return $fromArray;
        }

        if ($fromSingle === ['advisor@bmw.com'] && $fromArray !== []) {
            return $fromArray;
        }

        return $fromSingle !== [] ? $fromSingle : ($fromArray !== [] ? $fromArray : ['advisor@bmw.com']);
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

    public function notifyMailDeliveryFailure(string $recipientEmail, string $mailContext, string $errorMessage): void
    {
        $this->sendToAdmins(
            new MailDeliveryFailureNotification(
                recipientEmail: trim($recipientEmail) !== '' ? trim($recipientEmail) : 'unknown',
                mailContext: trim($mailContext) !== '' ? trim($mailContext) : 'Showroom Mailer',
                errorMessage: trim($errorMessage) !== '' ? trim($errorMessage) : 'Không thể phát email qua kênh chính.',
            ),
            'mail_delivery_failure',
            0
        );
    }

    public function notifySalesLeadAssigned(
        Appointment|AccessoryOrder $record,
        User $assignee,
        ?User $assignedBy = null,
    ): void {
        try {
            $assignee->notify(new LeadAssignedNotification($record, $assignedBy));
            $this->forgetDigestCache((int) $assignee->id);
        } catch (Throwable $exception) {
            Log::warning('Failed dispatching sales lead assignment notification.', [
                'record_id' => $record->id,
                'assignee_id' => $assignee->id,
                'exception' => $exception::class,
            ]);
        }
    }

    private function sendToAdmins(BaseNotification $notification, string $entityType, int $entityId): void
    {
        try {
            $admins = User::query()
                ->salesAndAdmins()
                ->get()
                ->filter(fn (User $user): bool => $user->canAccessCrm())
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
