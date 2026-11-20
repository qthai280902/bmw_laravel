<?php

namespace App\Livewire\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Exceptions\InvalidStatusTransitionException;
use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\WithBmwPagination;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

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

    #[Url(as: 'today', except: false)]
    public bool $onlyToday = false;

    public ?int $inspectedBookingId = null;

    public ?string $flashMessage = null;

    public ?string $errorMessage = null;

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

    public function applyQuickFilter(string $preset): void
    {
        $this->authorizeCrm();

        if ($preset === 'pending') {
            $isActive = $this->status === AppointmentStatus::Pending->value && ! $this->onlyToday;
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AppointmentStatus::Pending->value;
            }

            return;
        }

        if ($preset === 'confirmed') {
            $isActive = $this->status === AppointmentStatus::Confirmed->value && ! $this->onlyToday;
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AppointmentStatus::Confirmed->value;
            }

            return;
        }

        if ($preset === 'today') {
            $isActive = $this->onlyToday && $this->status === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->onlyToday = true;
            }

            return;
        }

        if ($preset === 'completed') {
            $isActive = $this->status === AppointmentStatus::Completed->value && ! $this->onlyToday;
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AppointmentStatus::Completed->value;
            }

            return;
        }

        $this->resetFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->serviceType = '';
        $this->onlyToday = false;
        $this->resetPage();
    }

    public function updateStatus(int $appointmentId, string $status): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag(['status', 'statusTransition']);
        $this->errorMessage = null;

        $validator = Validator::make(
            ['status' => $status],
            ['status' => ['required', 'string', Rule::enum(AppointmentStatus::class)]],
            ['status.*' => 'Trạng thái cập nhật không hợp lệ hoặc sai định dạng.'],
        );

        if ($validator->fails()) {
            $message = (string) $validator->errors()->first('status');
            $this->flashMessage = null;
            $this->errorMessage = $message;
            $this->addError('status', $message);
            $this->addError('statusTransition', $message);

            return;
        }

        $appointment = Appointment::query()
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($appointmentId);

        $nextStatus = AppointmentStatus::from((string) $validator->validated()['status']);

        try {
            $transitioned = $appointment->transitionStatusWithAutomation($nextStatus, auth()->user());

            if (! $transitioned) {
                $staleMessage = "Lịch chăm sóc xe #{$appointment->id} hiện đã ở trạng thái '{$nextStatus->label()}' trước đó (có thể đã được nhân sự khác xử lý).";
                $this->flashMessage = null;
                $this->errorMessage = $staleMessage;
                $this->addError('statusTransition', $staleMessage);

                return;
            }

            $this->flashMessage = "Đã cập nhật trạng thái lịch chăm sóc xe #{$appointment->id} thành công.";
        } catch (InvalidStatusTransitionException $exception) {
            $this->flashMessage = null;
            $this->errorMessage = $exception->getMessage();
            $this->addError('statusTransition', $exception->getMessage());
        }
    }

    public function resendConfirmationEmail(int $appointmentId): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag('statusTransition');
        $this->errorMessage = null;

        $appointment = Appointment::query()
            ->with(['product.category', 'user'])
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($appointmentId);

        if ($appointment->isTerminalStatus()) {
            $this->flashMessage = null;
            $this->errorMessage = "Lịch chăm sóc xe #{$appointment->id} đã ở trạng thái đóng ({$appointment->statusLabel()}), không thể gửi lại thư xác nhận.";
            $this->addError('statusTransition', $this->errorMessage);

            return;
        }

        $recipientEmail = trim((string) ($appointment->user?->email ?? $appointment->guest_email ?? ''));

        if ($recipientEmail === '') {
            $this->flashMessage = "Lịch chăm sóc xe #{$appointment->id} chưa có địa chỉ email để gửi thư xác nhận.";

            return;
        }

        if ($appointment->sendOfficialConfirmationEmail(auth()->user())) {
            $this->flashMessage = "Đã gửi thư Xác nhận chính thức tới {$recipientEmail} cho lịch chăm sóc xe #{$appointment->id}.";
        } else {
            $this->flashMessage = "Không thể gửi thư xác nhận chính thức cho lịch chăm sóc xe #{$appointment->id}.";
        }
    }

    public function inspectBooking(int $bookingId): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag('statusTransition');
        $this->errorMessage = null;

        $this->inspectedBookingId = $bookingId;
    }

    public function closeInspector(): void
    {
        $this->inspectedBookingId = null;
    }

    #[Computed]
    public function inspectedBooking(): ?Appointment
    {
        if ($this->inspectedBookingId === null) {
            return null;
        }

        return Appointment::query()
            ->with(['product', 'user'])
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->find($this->inspectedBookingId);
    }

    public function render(): View
    {
        $this->authorizeCrm();

        $serviceTypeValues = $this->allowedServiceTypeValues();
        $todayStart = now()->startOfDay()->toDateTimeString();
        $todayEnd = now()->endOfDay()->toDateTimeString();

        $statsRow = Appointment::query()
            ->toBase()
            ->whereIn('type', $serviceTypeValues)
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed_count,
                 SUM(CASE WHEN appointment_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count',
                [
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Confirmed->value,
                    $todayStart,
                    $todayEnd,
                    AppointmentStatus::Completed->value,
                ]
            )
            ->first();

        $query = Appointment::query()
            ->with(['product', 'user'])
            ->whereIn('type', $serviceTypeValues);

        if (in_array($this->status, array_column(AppointmentStatus::cases(), 'value'), true)) {
            $query->where('status', $this->status);
        }

        if (in_array($this->serviceType, $serviceTypeValues, true)) {
            $query->where('type', $this->serviceType);
        }

        if ($this->onlyToday) {
            $query->whereBetween('appointment_date', [$todayStart, $todayEnd]);
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
            'quickStats' => [
                'pending' => (int) ($statsRow->pending_count ?? 0),
                'confirmed' => (int) ($statsRow->confirmed_count ?? 0),
                'today' => (int) ($statsRow->today_count ?? 0),
                'completed' => (int) ($statsRow->completed_count ?? 0),
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
