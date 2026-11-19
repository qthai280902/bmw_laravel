<?php

namespace App\Listeners;

use App\Enums\AppointmentType;
use App\Events\AppointmentStatusChanged;
use App\Events\CatalogProductUpdated;
use App\Events\PromotionCampaignDispatched;
use App\Events\StaffAccountUpdated;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminActivitySubscriber
{
    public function onLogin(Login $event): void
    {
        if (! $event->user instanceof User || ! $event->user->isAdmin()) {
            return;
        }

        $this->record(
            user: $event->user,
            action: 'auth.login',
            description: "Nhân sự {$event->user->name} ({$event->user->roleLabel()}) đăng nhập cổng quản trị.",
        );
    }

    public function onLogout(Logout $event): void
    {
        if (! $event->user instanceof User || ! $event->user->isAdmin()) {
            return;
        }

        $this->record(
            user: $event->user,
            action: 'auth.logout',
            description: "Nhân sự {$event->user->name} ({$event->user->roleLabel()}) đăng xuất khỏi hệ thống.",
        );
    }

    public function onPromotionCampaignDispatched(PromotionCampaignDispatched $event): void
    {
        $actor = $this->resolveActor();
        $recipientCount = count($event->recipients);

        $this->record(
            user: $actor,
            action: 'promotion.dispatched',
            description: "Phát chiến dịch ưu đãi \"{$event->offerTitle}\" tới {$recipientCount} khách hàng mục tiêu.",
        );
    }

    public function onAppointmentStatusChanged(AppointmentStatusChanged $event): void
    {
        $actor = $event->actor ?? $this->resolveActor();
        $appointment = $event->appointment;
        $isService = in_array(
            $appointment->type,
            [AppointmentType::Maintenance, AppointmentType::Detailing, AppointmentType::CarWash],
            true
        );
        $categoryLabel = $isService ? 'lịch dịch vụ chăm sóc xe' : 'lịch hẹn';

        $this->record(
            user: $actor,
            action: 'appointment.status_changed',
            description: "Chuyển trạng thái {$categoryLabel} #{$appointment->id} ({$appointment->customer_name}) từ {$event->oldStatus} sang {$event->newStatus}.",
        );
    }

    public function onStaffAccountUpdated(StaffAccountUpdated $event): void
    {
        $actor = $event->actor ?? $this->resolveActor();

        $this->record(
            user: $actor,
            action: $event->action,
            description: $event->description,
        );
    }

    public function onCatalogProductUpdated(CatalogProductUpdated $event): void
    {
        $actor = $event->actor ?? $this->resolveActor();

        $this->record(
            user: $actor,
            action: 'product.updated',
            description: $event->description,
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            PromotionCampaignDispatched::class => 'onPromotionCampaignDispatched',
            AppointmentStatusChanged::class => 'onAppointmentStatusChanged',
            StaffAccountUpdated::class => 'onStaffAccountUpdated',
            CatalogProductUpdated::class => 'onCatalogProductUpdated',
        ];
    }

    private function resolveActor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private function record(?User $user, string $action, string $description): void
    {
        try {
            $ipAddress = app()->bound('request') ? request()->ip() : null;

            AdminActivityLog::query()->create([
                'user_id' => $user?->id,
                'user_email' => $user?->email ?? 'system@bmw.com',
                'user_role' => $user?->normalizedRole() ?? 'system',
                'action' => $action,
                'description' => mb_substr($description, 0, 255),
                'ip_address' => is_string($ipAddress) ? mb_substr($ipAddress, 0, 45) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed writing admin activity log.', [
                'action' => $action,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
