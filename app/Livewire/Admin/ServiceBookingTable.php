<?php

namespace App\Livewire\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\WithBmwPagination;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class ServiceBookingTable extends Component
{
    use AuthorizesAdminLivewire;
    use WithBmwPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'service_type', except: '')]
    public string $serviceType = '';

    public ?string $flashMessage = null;

    public function mount(): void
    {
        $this->authorizeCrm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedServiceType(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->serviceType = '';
        $this->resetPage();
    }

    public function updateStatus(int $appointmentId, string $status): void
    {
        $this->authorizeCrm();

        $validated = Validator::make(
            ['status' => $status],
            ['status' => ['required', Rule::enum(AppointmentStatus::class)]],
        )->validate();

        $appointment = Appointment::query()
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($appointmentId);

        $appointment->update([
            'status' => $validated['status'],
        ]);

        $this->flashMessage = "Đã cập nhật trạng thái lịch chăm sóc xe #{$appointment->id} thành công.";
    }

    public function resendConfirmationEmail(int $appointmentId): void
    {
        $this->authorizeCrm();

        $appointment = Appointment::query()
            ->with(['product.category', 'user'])
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($appointmentId);

        $recipientEmail = trim((string) ($appointment->user?->email ?? $appointment->guest_email ?? ''));

        if ($recipientEmail === '') {
            $this->flashMessage = "Lịch chăm sóc xe #{$appointment->id} chưa có địa chỉ email để gửi lại.";

            return;
        }

        try {
            Mail::to($recipientEmail)->send(new AppointmentConfirmationMail($appointment));
            $this->flashMessage = "Đã gửi lại email xác nhận tới {$recipientEmail} cho lịch chăm sóc xe #{$appointment->id}.";
        } catch (Throwable $exception) {
            Log::warning('Failed resending service booking confirmation email from CRM.', [
                'appointment_id' => $appointment->id,
                'exception' => $exception::class,
            ]);

            $this->flashMessage = "Không thể gửi lại email xác nhận cho lịch chăm sóc xe #{$appointment->id}.";
        }
    }

    public function render(): View
    {
        $this->authorizeCrm();

        $serviceTypeValues = $this->allowedServiceTypeValues();
        $query = Appointment::query()
            ->with(['product', 'user'])
            ->whereIn('type', $serviceTypeValues);

        if (in_array($this->status, array_column(AppointmentStatus::cases(), 'value'), true)) {
            $query->where('status', $this->status);
        }

        if (in_array($this->serviceType, $serviceTypeValues, true)) {
            $query->where('type', $this->serviceType);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('guest_name', 'like', $like)
                    ->orWhere('guest_phone', 'like', $like)
                    ->orWhere('guest_email', 'like', $like)
                    ->orWhere('meta_data', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('product', function (Builder $productQuery) use ($like): void {
                        $productQuery->where('name', 'like', $like);
                    });
            });
        }

        return view('livewire.admin.service-booking-table', [
            'bookings' => $query->latest('appointment_date')->paginate(15),
            'statuses' => AppointmentStatus::cases(),
            'serviceTypes' => [
                AppointmentType::Maintenance,
                AppointmentType::Detailing,
                AppointmentType::CarWash,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function allowedServiceTypeValues(): array
    {
        return [
            AppointmentType::Maintenance->value,
            AppointmentType::Detailing->value,
            AppointmentType::CarWash->value,
        ];
    }
}
