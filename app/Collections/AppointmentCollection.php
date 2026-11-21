<?php

namespace App\Collections;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, Appointment>
 */
class AppointmentCollection extends Collection
{
    public function vehicleBookings(): self
    {
        return $this->filter(static fn (Appointment $appointment): bool => ! $appointment->isServiceBooking());
    }

    public function serviceBookings(): self
    {
        return $this->filter(static fn (Appointment $appointment): bool => $appointment->isServiceBooking());
    }

    /**
     * @return array{total: int, pending: int, confirmed: int, completed: int, cancelled: int}
     */
    public function statusCounts(): array
    {
        $counts = [
            'total' => $this->count(),
            'pending' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($this as $appointment) {
            $status = $appointment->status instanceof AppointmentStatus
                ? $appointment->status->value
                : (string) $appointment->status;

            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    public function committedDepositValue(): int
    {
        return (int) $this->sum(static function (Appointment $appointment): int {
            if ($appointment->isServiceBooking()) {
                return 0;
            }

            $status = $appointment->status instanceof AppointmentStatus
                ? $appointment->status
                : AppointmentStatus::tryFrom((string) $appointment->status);

            if (! in_array($status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed], true)) {
                return 0;
            }

            return self::resolveDepositAmount($appointment);
        });
    }

    public function cancelledDepositValue(): int
    {
        return (int) $this->sum(static function (Appointment $appointment): int {
            if ($appointment->isServiceBooking()) {
                return 0;
            }

            $status = $appointment->status instanceof AppointmentStatus
                ? $appointment->status
                : AppointmentStatus::tryFrom((string) $appointment->status);

            if ($status !== AppointmentStatus::Cancelled) {
                return 0;
            }

            return self::resolveDepositAmount($appointment);
        });
    }

    public function referenceVehicleValue(): int
    {
        return (int) $this->sum(static function (Appointment $appointment): int {
            if ($appointment->isServiceBooking()) {
                return 0;
            }

            $status = $appointment->status instanceof AppointmentStatus
                ? $appointment->status
                : AppointmentStatus::tryFrom((string) $appointment->status);

            if (! in_array($status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed], true)) {
                return 0;
            }

            return (int) ($appointment->product?->price ?? 0);
        });
    }

    public function completionRate(): float
    {
        $counts = $this->statusCounts();

        return $counts['total'] > 0
            ? round(($counts['completed'] / $counts['total']) * 100, 1)
            : 0.0;
    }

    public static function resolveDepositAmount(Appointment $appointment): int
    {
        $refPrice = (int) ($appointment->product?->price ?? 0);
        $deposit = (int) ($appointment->product?->deposit_amount ?? 0);

        if ($deposit <= 0 && $refPrice > 0) {
            return 50000000;
        }

        return $deposit;
    }
}
