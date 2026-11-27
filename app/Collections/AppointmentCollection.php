<?php

namespace App\Collections;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\Crm\InteractionTimelineRecorder;
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

            if ($status !== AppointmentStatus::Confirmed) {
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

    public function realizedVehicleRevenue(): int
    {
        return (int) $this->sum(static function (Appointment $appointment): int {
            if ($appointment->isServiceBooking()) {
                return 0;
            }

            $status = $appointment->status instanceof AppointmentStatus
                ? $appointment->status
                : AppointmentStatus::tryFrom((string) $appointment->status);

            if ($status !== AppointmentStatus::Completed) {
                return 0;
            }

            return self::resolveVehicleSaleRevenue($appointment);
        });
    }

    public function realizedServiceRevenue(): int
    {
        return (int) $this->sum(static function (Appointment $appointment): int {
            if (! $appointment->isServiceBooking()) {
                return 0;
            }

            $status = $appointment->status instanceof AppointmentStatus
                ? $appointment->status
                : AppointmentStatus::tryFrom((string) $appointment->status);

            if ($status !== AppointmentStatus::Completed) {
                return 0;
            }

            return self::resolveServiceRevenue($appointment);
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

            if ($status === AppointmentStatus::Completed) {
                return self::resolveVehicleSaleRevenue($appointment);
            }

            if ($status === AppointmentStatus::Confirmed && self::resolveDepositAmount($appointment) > 0) {
                return (int) ($appointment->product?->price ?? 0);
            }

            return 0;
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
        $meta = InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);

        return max(0, (int) ($meta['actual_deposit_amount'] ?? 0));
    }

    public static function resolveVehicleSaleRevenue(Appointment $appointment): int
    {
        if ($appointment->isServiceBooking() || $appointment->completionOutcome() !== Appointment::OUTCOME_PURCHASED) {
            return 0;
        }

        $meta = InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);
        $finalPrice = (int) ($meta['final_vehicle_price'] ?? 0);

        if ($finalPrice > 0) {
            return $finalPrice;
        }

        return max(0, (int) ($appointment->product?->price ?? 0));
    }

    public static function resolveServiceRevenue(Appointment $appointment): int
    {
        if (! $appointment->isServiceBooking()) {
            return 0;
        }

        $meta = InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);

        return max(0, (int) ($meta['service_actual_cost'] ?? 0));
    }
}
