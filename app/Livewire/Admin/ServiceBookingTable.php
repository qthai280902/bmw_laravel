<?php

namespace App\Livewire\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Exceptions\InvalidStatusTransitionException;
use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\HandlesCrmArchiveActions;
use App\Livewire\Concerns\WithBmwPagination;
use App\Models\Appointment;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\SalesAssignmentService;
use Carbon\CarbonImmutable;
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
    use HandlesCrmArchiveActions;
    use WithBmwPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'service_type', except: '')]
    public string $serviceType = '';

    #[Url(as: 'sales_id', except: '')]
    public string $salesId = '';

    #[Url(as: 'date_preset', except: '')]
    public string $datePreset = '';

    #[Url(as: 'date_from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'date_to', except: '')]
    public string $dateTo = '';

    #[Url(as: 'today', except: false)]
    public bool $onlyToday = false;

    public ?int $inspectedBookingId = null;

    public ?string $flashMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorizeCrm();

        if ($this->onlyToday && $this->datePreset === '') {
            $this->datePreset = 'today';
        }
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

    public function updatedSalesId(): void
    {
        $this->resetPage();
    }

    public function updatedDatePreset(): void
    {
        $this->onlyToday = $this->datePreset === 'today';
        if ($this->datePreset !== 'custom') {
            $this->dateFrom = '';
            $this->dateTo = '';
        }
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        if ($this->dateFrom !== '') {
            $this->datePreset = 'custom';
            $this->onlyToday = false;
        }
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        if ($this->dateTo !== '') {
            $this->datePreset = 'custom';
            $this->onlyToday = false;
        }
        $this->resetPage();
    }

    public function applyQuickFilter(string $preset): void
    {
        $this->authorizeCrm();

        if ($preset === 'pending') {
            $isActive = $this->status === AppointmentStatus::Pending->value && ! $this->onlyToday && $this->datePreset === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AppointmentStatus::Pending->value;
            }

            return;
        }

        if ($preset === 'confirmed') {
            $isActive = $this->status === AppointmentStatus::Confirmed->value && ! $this->onlyToday && $this->datePreset === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AppointmentStatus::Confirmed->value;
            }

            return;
        }

        if ($preset === 'today') {
            $isActive = ($this->onlyToday || $this->datePreset === 'today') && $this->status === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->onlyToday = true;
                $this->datePreset = 'today';
            }

            return;
        }

        if ($preset === 'completed') {
            $isActive = $this->status === AppointmentStatus::Completed->value && ! $this->onlyToday && $this->datePreset === '';
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
        $this->salesId = '';
        $this->datePreset = '';
        $this->dateFrom = '';
        $this->dateTo = '';
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

    public function assignToSales(int $bookingId, string|int|null $salesUserId, SalesAssignmentService $assignmentService): void
    {
        $this->authorizeSystemManagement();
        $this->resetErrorBag('statusTransition');
        $this->errorMessage = null;

        $booking = Appointment::query()
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($bookingId);
        $targetId = filled($salesUserId) ? (int) $salesUserId : null;

        try {
            $assignedUser = $assignmentService->assignManually($booking, $targetId, auth()->user());
            unset($this->inspectedBooking);

            $this->flashMessage = $assignedUser
                ? "Đã phân công lịch dịch vụ {$booking->customer_order_code} cho {$assignedUser->name}."
                : "Đã gỡ phân công Sales cho lịch dịch vụ {$booking->customer_order_code}.";
        } catch (\Throwable $exception) {
            $this->flashMessage = null;
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function acknowledgeGuestReschedule(int $bookingId): void
    {
        $booking = Appointment::query()
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($bookingId);
        $this->acknowledgeAppointmentReschedule($booking, 'lịch dịch vụ');
        unset($this->inspectedBooking);
    }

    public function archiveRecord(int $bookingId): void
    {
        $this->authorizeTopSuperAdmin();
        $booking = Appointment::query()
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($bookingId);
        $this->performArchive($booking, 'lịch dịch vụ');
        unset($this->inspectedBooking);
    }

    public function unarchiveRecord(int $bookingId): void
    {
        $this->authorizeTopSuperAdmin();
        $booking = Appointment::query()
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->findOrFail($bookingId);
        $this->performUnarchive($booking, 'lịch dịch vụ');
        unset($this->inspectedBooking);
    }

    public function archiveAllClosed(ShowroomCacheService $cacheService): void
    {
        $this->authorizeTopSuperAdmin();
        $this->errorMessage = null;

        $count = Appointment::query()
            ->activeRecords()
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->whereIn('status', [AppointmentStatus::Completed->value, AppointmentStatus::Cancelled->value])
            ->toBase()
            ->update([
                'archived_at' => now(),
                'archived_by' => auth()->id(),
            ]);

        $cacheService->flushDashboard();
        $this->resetPage();
        $this->flashMessage = $count > 0
            ? "Đã chuyển {$count} lịch dịch vụ đã đóng vào Kho lưu trữ CRM."
            : 'Không có lịch dịch vụ đã đóng nào cần lưu trữ thêm.';
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
            ->with(['product', 'user', 'assignedSales', 'archivedBy:id,name'])
            ->whereIn('type', $this->allowedServiceTypeValues())
            ->find($this->inspectedBookingId);
    }

    public function render(ShowroomCacheService $cacheService): View
    {
        $this->authorizeCrm();

        $serviceTypeValues = $this->allowedServiceTypeValues();
        $todayStart = now()->startOfDay()->toDateTimeString();
        $todayEnd = now()->endOfDay()->toDateTimeString();
        [$rangeStart, $rangeEnd] = $this->resolveDateRange();

        $statsRow = Appointment::query()
            ->toBase()
            ->whereIn('type', $serviceTypeValues)
            ->selectRaw(
                'SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as confirmed_count,
                 SUM(CASE WHEN archived_at IS NULL AND appointment_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as completed_count,
                 SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) as archived_count',
                [
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Confirmed->value,
                    $todayStart,
                    $todayEnd,
                    AppointmentStatus::Completed->value,
                ]
            )
            ->first();

        $salesRoster = $cacheService->salesRoster();

        $query = Appointment::query()
            ->leftJoin('users as assigned_sales', 'appointments.assigned_to', '=', 'assigned_sales.id')
            ->select('appointments.*', 'assigned_sales.name as assigned_sales_name')
            ->with(['product', 'user'])
            ->whereIn('appointments.type', $serviceTypeValues)
            ->when(
                $this->archiveScope === 'archived',
                fn (Builder $builder) => $builder->archived(),
                fn (Builder $builder) => $builder->activeRecords(),
            );

        if (in_array($this->status, array_column(AppointmentStatus::cases(), 'value'), true)) {
            $query->where('appointments.status', $this->status);
        }

        if (in_array($this->serviceType, $serviceTypeValues, true)) {
            $query->where('appointments.type', $this->serviceType);
        }

        if ($this->salesId !== '') {
            if ($this->salesId === 'mine') {
                $query->where('appointments.assigned_to', auth()->id());
            } elseif ($this->salesId === 'unassigned') {
                $query->whereNull('appointments.assigned_to');
            } elseif (is_numeric($this->salesId)) {
                $query->where('appointments.assigned_to', (int) $this->salesId);
            }
        }

        if ($rangeStart !== null && $rangeEnd !== null) {
            $query->whereBetween('appointments.appointment_date', [$rangeStart, $rangeEnd]);
        } elseif ($rangeStart !== null) {
            $query->where('appointments.appointment_date', '>=', $rangeStart);
        } elseif ($rangeEnd !== null) {
            $query->where('appointments.appointment_date', '<=', $rangeEnd);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $builder) use ($like, $search): void {
                if (is_numeric(ltrim($search, '#'))) {
                    $builder->orWhere('appointments.id', (int) ltrim($search, '#'));
                }

                $builder->orWhere('appointments.customer_order_code', 'like', $like)
                    ->orWhere('appointments.daily_sequence_code', 'like', $like)
                    ->orWhere('appointments.customer_internal_code', 'like', $like)
                    ->orWhere('appointments.guest_name', 'like', $like)
                    ->orWhere('appointments.guest_phone', 'like', $like)
                    ->orWhere('appointments.guest_email', 'like', $like)
                    ->orWhere('appointments.meta_data', 'like', $like)
                    ->orWhere('appointments.notes', 'like', $like)
                    ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('product', function (Builder $productQuery) use ($like): void {
                        $productQuery->where('name', 'like', $like);
                    });
            });
        }

        $activeFilterCount = collect([
            $search !== '',
            $this->status !== '',
            $this->serviceType !== '',
            $this->salesId !== '',
            $this->datePreset !== '' || $this->onlyToday || $this->dateFrom !== '' || $this->dateTo !== '',
        ])->filter()->count();

        return view('livewire.admin.service-booking-table', [
            'bookings' => $query->latest('appointments.appointment_date')->paginate(15),
            'statuses' => AppointmentStatus::cases(),
            'serviceTypes' => [
                AppointmentType::Maintenance,
                AppointmentType::Detailing,
                AppointmentType::CarWash,
            ],
            'salesRoster' => $salesRoster,
            'activeFilterCount' => $activeFilterCount,
            'quickStats' => [
                'pending' => (int) ($statsRow->pending_count ?? 0),
                'confirmed' => (int) ($statsRow->confirmed_count ?? 0),
                'today' => (int) ($statsRow->today_count ?? 0),
                'completed' => (int) ($statsRow->completed_count ?? 0),
                'archived' => (int) ($statsRow->archived_count ?? 0),
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

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveDateRange(): array
    {
        if ($this->onlyToday && ($this->datePreset === '' || $this->datePreset === 'today')) {
            return [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()];
        }

        $tz = 'Asia/Ho_Chi_Minh';
        $appTz = (string) config('app.timezone', 'UTC');
        $nowVn = CarbonImmutable::now($tz);

        if ($this->datePreset === 'today') {
            return [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()];
        }

        if ($this->datePreset === '7d') {
            return [
                $nowVn->subDays(6)->startOfDay()->setTimezone($appTz)->toDateTimeString(),
                $nowVn->endOfDay()->setTimezone($appTz)->toDateTimeString(),
            ];
        }

        if ($this->datePreset === '30d') {
            return [
                $nowVn->subDays(29)->startOfDay()->setTimezone($appTz)->toDateTimeString(),
                $nowVn->endOfDay()->setTimezone($appTz)->toDateTimeString(),
            ];
        }

        if ($this->datePreset === 'this_month') {
            return [
                $nowVn->startOfMonth()->setTimezone($appTz)->toDateTimeString(),
                $nowVn->endOfMonth()->setTimezone($appTz)->toDateTimeString(),
            ];
        }

        if ($this->datePreset === 'custom' || $this->dateFrom !== '' || $this->dateTo !== '') {
            $start = null;
            $end = null;

            if ($this->dateFrom !== '') {
                try {
                    $start = CarbonImmutable::parse($this->dateFrom, $tz)->startOfDay()->setTimezone($appTz)->toDateTimeString();
                } catch (\Throwable) {
                }
            }

            if ($this->dateTo !== '') {
                try {
                    $end = CarbonImmutable::parse($this->dateTo, $tz)->endOfDay()->setTimezone($appTz)->toDateTimeString();
                } catch (\Throwable) {
                }
            }

            return [$start, $end];
        }

        return [null, null];
    }
}
