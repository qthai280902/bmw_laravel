<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Ai\AiConversationTracker;
use App\Services\Ai\AppointmentLeadClassifier;

class AppointmentObserver
{
    public function __construct(
        private AppointmentLeadClassifier $classifier,
        private AiConversationTracker $tracker,
        private AdminNotificationDispatcher $notificationDispatcher,
    ) {}

    public function created(Appointment $appointment): void
    {
        $request = app()->bound('request') ? request() : null;
        $this->tracker->linkAppointment($appointment, $request);

        $this->classifier->classifyAndSave($appointment);

        $this->notificationDispatcher->dispatchNewAppointmentLead($appointment);
    }
}
