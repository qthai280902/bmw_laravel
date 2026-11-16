<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\Ai\AppointmentLeadClassifier;

class AppointmentObserver
{
    public function __construct(
        private AppointmentLeadClassifier $classifier,
    ) {}

    public function created(Appointment $appointment): void
    {
        $this->classifier->classifyAndSave($appointment);
    }
}
