<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Ai\AiConversationTracker;
use App\Services\Ai\AppointmentLeadClassifier;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\InteractionTimelineRecorder;

class AppointmentObserver
{
    public function __construct(
        private AppointmentLeadClassifier $classifier,
        private AiConversationTracker $tracker,
        private AdminNotificationDispatcher $notificationDispatcher,
        private ?ShowroomCacheService $cacheService = null,
    ) {}

    public function created(Appointment $appointment): void
    {
        $request = app()->bound('request') ? request() : null;
        $this->tracker->linkAppointment($appointment, $request);

        $this->classifier->classifyAndSave($appointment);

        $appointment->loadMissing('product');
        $channel = filled($appointment->ai_conversation_id) || filled($appointment->ai_visitor_id)
            ? 'AI Chat'
            : 'Web';
        $targetLabel = $appointment->product?->name
            ?? ($appointment->servicePackageLabel() ?: $appointment->typeLabel());
        $dateText = $appointment->appointment_date
            ? ' vào lúc '.$appointment->appointment_date->format('H:i · d/m/Y')
            : '';

        InteractionTimelineRecorder::record(
            model: $appointment,
            actorType: InteractionTimelineRecorder::ACTOR_CUSTOMER,
            actorLabel: 'Khách hàng · '.$appointment->customer_name,
            title: "Gửi yêu cầu {$appointment->typeLabel()}",
            detail: "Khách đặt lịch cho {$targetLabel}{$dateText}.",
            channel: $channel,
            occurredAt: $appointment->created_at ?? now(),
        );

        $this->cacheService()->flushDashboard();
        $this->notificationDispatcher->dispatchNewAppointmentLead($appointment);
    }

    public function saved(Appointment $appointment): void
    {
        $this->cacheService()->flushDashboard();
    }

    public function deleted(Appointment $appointment): void
    {
        $this->cacheService()->flushDashboard();
    }

    private function cacheService(): ShowroomCacheService
    {
        return $this->cacheService ??= app(ShowroomCacheService::class);
    }
}
