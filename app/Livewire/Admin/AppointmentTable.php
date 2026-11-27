<?php

namespace App\Livewire\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Exceptions\InvalidStatusTransitionException;
use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\HandlesCrmArchiveActions;
use App\Livewire\Concerns\WithBmwPagination;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\SalesAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class AppointmentTable extends Component
{
    use AuthorizesAdminLivewire;
    use HandlesCrmArchiveActions;
    use WithBmwPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'priority', except: '')]
    public string $leadPriority = '';

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

    public ?int $inspectedAppointmentId = null;

    public ?int $completionTargetId = null;

    public string $completionTargetReference = '';

    public string $completionTargetCategory = 'sales';

    public string $completionOutcome = '';

    public string $completionNote = '';

    public string $completionActualCostInput = '';

    public int $completionReminderMonths = 0;

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

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedLeadPriority(): void
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
            $isActive = $this->status === AppointmentStatus::Pending->value && $this->leadPriority === '' && ! $this->onlyToday && $this->datePreset === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AppointmentStatus::Pending->value;
            }

            return;
        }

        if ($preset === 'hot') {
            $isActive = $this->leadPriority === Appointment::LEAD_PRIORITY_HOT && $this->status === '' && ! $this->onlyToday && $this->datePreset === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->leadPriority = Appointment::LEAD_PRIORITY_HOT;
            }

            return;
        }

        if ($preset === 'today') {
            $isActive = ($this->onlyToday || $this->datePreset === 'today') && $this->status === '' && $this->leadPriority === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->onlyToday = true;
                $this->datePreset = 'today';
            }

            return;
        }

        if ($preset === 'completed') {
            $isActive = $this->status === AppointmentStatus::Completed->value && $this->leadPriority === '' && ! $this->onlyToday && $this->datePreset === '';
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
        $this->type = '';
        $this->leadPriority = '';
        $this->salesId = '';
        $this->datePreset = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->onlyToday = false;
        $this->resetPage();
    }

    public function openCompletionModal(int $appointmentId): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag(['status', 'statusTransition', 'completionOutcome']);
        $this->errorMessage = null;

        $appointment = Appointment::query()->findOrFail($appointmentId);

        if ($appointment->hasPendingRescheduleReview()) {
            $msg = 'Khách hàng vừa đổi lịch hẹn. Vui lòng bấm "Xác nhận đã tiếp nhận lịch mới" trước khi hoàn tất hồ sơ.';
            $this->flashMessage = null;
            $this->errorMessage = $msg;
            $this->addError('statusTransition', $msg);

            return;
        }

        if ($appointment->status !== AppointmentStatus::Confirmed) {
            $this->updateStatus($appointmentId, AppointmentStatus::Completed->value);

            return;
        }

        $this->completionTargetId = $appointment->id;
        $this->completionTargetReference = $appointment->publicReferenceCode();
        $this->completionTargetCategory = $appointment->type instanceof AppointmentType
            ? $appointment->type->completionCategory()
            : 'sales';
        $this->completionOutcome = '';
        $this->completionNote = '';
        $this->completionActualCostInput = '';
        $this->completionReminderMonths = 0;
    }

    public function selectCompletionOutcome(string $outcome): void
    {
        $this->authorizeCrm();

        if (! in_array($outcome, Appointment::allCompletionOutcomes(), true)) {
            return;
        }

        $this->completionOutcome = $outcome;
        $this->resetErrorBag('completionOutcome');
        $this->errorMessage = null;
    }

    public function closeCompletionModal(): void
    {
        $this->completionTargetId = null;
        $this->completionTargetReference = '';
        $this->completionTargetCategory = 'sales';
        $this->completionOutcome = '';
        $this->completionNote = '';
        $this->completionActualCostInput = '';
        $this->completionReminderMonths = 0;
        $this->resetErrorBag('completionOutcome');
    }

    public function confirmCompletion(): void
    {
        $this->authorizeCrm();

        if ($this->completionTargetId === null) {
            return;
        }

        $appointment = Appointment::query()->findOrFail($this->completionTargetId);
        $validOutcomes = $appointment->validCompletionOutcomes();

        if (! in_array($this->completionOutcome, $validOutcomes, true)) {
            $this->errorMessage = 'Vui lòng chọn kết quả thực tế trước khi hoàn tất hồ sơ.';
            $this->addError('completionOutcome', $this->errorMessage);

            return;
        }

        $rawCostDigits = preg_replace('/[^\d]/', '', $this->completionActualCostInput) ?? '';
        $payload = [
            'service_completion_note' => $this->completionNote,
            'service_actual_cost' => $rawCostDigits !== '' ? (int) $rawCostDigits : null,
            'service_next_reminder_months' => in_array($this->completionReminderMonths, [3, 6, 12], true) ? $this->completionReminderMonths : 0,
            'trade_in_note' => $this->completionNote,
        ];

        $targetId = $this->completionTargetId;
        $outcome = $this->completionOutcome;
        $this->closeCompletionModal();

        $this->updateStatus($targetId, AppointmentStatus::Completed->value, $outcome, $payload);
    }

    public function handleInlineStatusChange(int $appointmentId, string $status): void
    {
        $this->authorizeCrm();

        if ($status === AppointmentStatus::Completed->value) {
            $this->openCompletionModal($appointmentId);

            return;
        }

        $this->updateStatus($appointmentId, $status);
    }

    /**
     * @param  array<string, mixed>  $completionPayload
     */
    public function updateStatus(
        int $appointmentId,
        string $status,
        ?string $completionOutcome = null,
        array $completionPayload = [],
    ): void {
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

        $appointment = Appointment::query()->findOrFail($appointmentId);
        $nextStatus = AppointmentStatus::from((string) $validator->validated()['status']);

        try {
            $transitioned = $appointment->transitionStatusWithAutomation(
                $nextStatus,
                auth()->user(),
                $completionOutcome,
                $completionPayload,
            );
            unset($this->inspectedAppointment);

            if (! $transitioned) {
                $staleMessage = "Lịch hẹn #{$appointment->id} hiện đã ở trạng thái '{$nextStatus->label()}' trước đó (có thể đã được nhân sự khác xử lý).";
                $this->flashMessage = null;
                $this->errorMessage = $staleMessage;
                $this->addError('statusTransition', $staleMessage);

                return;
            }

            if ($nextStatus === AppointmentStatus::Completed && $completionOutcome === Appointment::OUTCOME_PURCHASED) {
                $voucher = $appointment->careVoucherCode();
                $this->flashMessage = "Đã hoàn tất lịch hẹn #{$appointment->id} (Khách đã mua xe · Mã BMW Care: {$voucher}).";
            } elseif ($nextStatus === AppointmentStatus::Completed && $completionOutcome === Appointment::OUTCOME_VISITED_NO_PURCHASE) {
                $this->flashMessage = "Đã hoàn tất lịch hẹn #{$appointment->id} (Khách đã tham quan / chưa mua xe · Đã gửi thư cảm ơn).";
            } elseif ($nextStatus === AppointmentStatus::Completed) {
                $label = $appointment->completionOutcomeLabel() ?? 'Hoàn tất';
                $this->flashMessage = "Đã hoàn tất lịch hẹn #{$appointment->id} ({$label} · Đã gửi thư xác nhận).";
            } else {
                $this->flashMessage = "Đã cập nhật trạng thái lịch hẹn #{$appointment->id} thành công.";
            }
        } catch (InvalidStatusTransitionException $exception) {
            $this->flashMessage = null;
            $this->errorMessage = $exception->getMessage();
            $this->addError('statusTransition', $exception->getMessage());
        }
    }

    public function assignToSales(int $appointmentId, string|int|null $salesUserId, SalesAssignmentService $assignmentService): void
    {
        $this->authorizeSystemManagement();
        $this->resetErrorBag(['statusTransition']);
        $this->errorMessage = null;

        $appointment = Appointment::query()->findOrFail($appointmentId);
        $targetId = filled($salesUserId) ? (int) $salesUserId : null;

        try {
            $assignedUser = $assignmentService->assignManually($appointment, $targetId, auth()->user());
            unset($this->inspectedAppointment);

            $this->flashMessage = $assignedUser
                ? "Đã phân công hồ sơ {$appointment->customer_order_code} cho {$assignedUser->name}."
                : "Đã gỡ phân công Sales cho hồ sơ {$appointment->customer_order_code}.";
        } catch (\Throwable $exception) {
            $this->flashMessage = null;
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function acknowledgeGuestReschedule(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);
        $this->acknowledgeAppointmentReschedule($appointment, 'lịch hẹn');
        unset($this->inspectedAppointment);
    }

    public function archiveRecord(int $appointmentId): void
    {
        $this->authorizeTopSuperAdmin();
        $appointment = Appointment::query()->findOrFail($appointmentId);
        $this->performArchive($appointment, 'lịch hẹn');
        unset($this->inspectedAppointment);
    }

    public function unarchiveRecord(int $appointmentId): void
    {
        $this->authorizeTopSuperAdmin();
        $appointment = Appointment::query()->findOrFail($appointmentId);
        $this->performUnarchive($appointment, 'lịch hẹn');
        unset($this->inspectedAppointment);
    }

    public function archiveAllClosed(ShowroomCacheService $cacheService): void
    {
        $this->authorizeTopSuperAdmin();
        $this->errorMessage = null;

        $count = Appointment::query()
            ->activeRecords()
            ->whereIn('status', [AppointmentStatus::Completed->value, AppointmentStatus::Cancelled->value])
            ->toBase()
            ->update([
                'archived_at' => now(),
                'archived_by' => auth()->id(),
            ]);

        $cacheService->flushDashboard();
        $this->resetPage();
        $this->flashMessage = $count > 0
            ? "Đã chuyển {$count} lịch hẹn đã đóng vào Kho lưu trữ CRM."
            : 'Không có lịch hẹn đã đóng nào cần lưu trữ thêm.';
    }

    public function resendConfirmationEmail(int $appointmentId): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag('statusTransition');
        $this->errorMessage = null;

        $appointment = Appointment::query()
            ->with(['product.category', 'user'])
            ->findOrFail($appointmentId);

        if ($appointment->isTerminalStatus()) {
            $this->flashMessage = null;
            $this->errorMessage = "Lịch hẹn #{$appointment->id} đã ở trạng thái đóng ({$appointment->statusLabel()}), không thể gửi lại thư xác nhận.";
            $this->addError('statusTransition', $this->errorMessage);

            return;
        }

        if ($appointment->hasPendingRescheduleReview()) {
            $this->flashMessage = null;
            $this->errorMessage = "Lịch hẹn #{$appointment->id} đang chờ duyệt đổi lịch mới. Vui lòng bấm \"Xác nhận đã tiếp nhận lịch mới\" thay vì gửi lại email cũ.";
            $this->addError('statusTransition', $this->errorMessage);

            return;
        }

        if ($appointment->status !== AppointmentStatus::Confirmed && $appointment->confirmation_email_sent_at !== null) {
            $this->flashMessage = null;
            $this->errorMessage = "Lịch hẹn #{$appointment->id} chưa được Duyệt & Xác nhận, không thể gửi lại thư xác nhận chính thức.";
            $this->addError('statusTransition', $this->errorMessage);

            return;
        }

        $recipientEmail = trim((string) ($appointment->user?->email ?? $appointment->guest_email ?? ''));

        if ($recipientEmail === '') {
            $this->flashMessage = "Lịch hẹn #{$appointment->id} chưa có địa chỉ email để gửi thư xác nhận.";

            return;
        }

        if ($appointment->sendOfficialConfirmationEmail(auth()->user(), true)) {
            unset($this->inspectedAppointment);
            $this->flashMessage = "Đã gửi lại thư Xác nhận chính thức từ Showroom tới {$recipientEmail} cho lịch hẹn #{$appointment->id}.";
        } else {
            $this->flashMessage = null;
            $this->errorMessage = 'Thư xác nhận vừa được gửi cách đây chưa đầy 30 giây. Vui lòng đợi ít phút trước khi gửi lại.';
            $this->addError('statusTransition', $this->errorMessage);
        }
    }

    public function inspectAppointment(int $appointmentId): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag('statusTransition');
        $this->errorMessage = null;

        $this->inspectedAppointmentId = $appointmentId;
    }

    public function inspectAiConversation(int $appointmentId): void
    {
        $this->inspectAppointment($appointmentId);
    }

    public function closeAiConversationModal(): void
    {
        $this->inspectedAppointmentId = null;
    }

    public function closeInspector(): void
    {
        $this->closeAiConversationModal();
    }

    #[Computed]
    public function inspectedAppointment(): ?Appointment
    {
        if ($this->inspectedAppointmentId === null) {
            return null;
        }

        return Appointment::query()
            ->with(['user', 'product', 'assignedSales', 'archivedBy:id,name', 'aiChatSession.messages'])
            ->find($this->inspectedAppointmentId);
    }

    #[Computed]
    public function inspectedAiSession(): ?AiChatSession
    {
        $appointment = $this->inspectedAppointment;
        if (! $appointment) {
            return null;
        }

        if ($appointment->aiChatSession) {
            return $appointment->aiChatSession;
        }

        if (filled($appointment->ai_visitor_id)) {
            return AiChatSession::query()
                ->with('messages')
                ->where('visitor_id', $appointment->ai_visitor_id)
                ->latest('last_seen_at')
                ->first();
        }

        return null;
    }

    public function render(ShowroomCacheService $cacheService): View
    {
        $keyword = trim($this->search);
        $todayStart = now()->startOfDay()->toDateTimeString();
        $todayEnd = now()->endOfDay()->toDateTimeString();
        [$rangeStart, $rangeEnd] = $this->resolveDateRange();

        // Single aggregate SQL query for contextual quick-filter strip (keeps <= 8 total queries)
        $statsRow = Appointment::query()
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN archived_at IS NULL AND lead_priority = ? THEN 1 ELSE 0 END) as hot_count,
                 SUM(CASE WHEN archived_at IS NULL AND appointment_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as completed_count,
                 SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) as archived_count',
                [
                    AppointmentStatus::Pending->value,
                    Appointment::LEAD_PRIORITY_HOT,
                    $todayStart,
                    $todayEnd,
                    AppointmentStatus::Completed->value,
                ]
            )
            ->first();

        $salesRoster = $cacheService->salesRoster();

        $appointments = Appointment::query()
            ->leftJoin('users as assigned_sales', 'appointments.assigned_to', '=', 'assigned_sales.id')
            ->select('appointments.*', 'assigned_sales.name as assigned_sales_name')
            ->with(['user', 'product'])
            ->when(
                $this->archiveScope === 'archived',
                fn ($query) => $query->archived(),
                fn ($query) => $query->activeRecords(),
            )
            ->when(
                $this->status !== '',
                fn ($query) => $query->where('appointments.status', $this->status),
            )
            ->when(
                $this->type !== '',
                fn ($query) => $query->where('appointments.type', $this->type),
            )
            ->when(
                in_array($this->leadPriority, [Appointment::LEAD_PRIORITY_HOT, Appointment::LEAD_PRIORITY_WARM, Appointment::LEAD_PRIORITY_NORMAL], true),
                fn ($query) => $query->where('appointments.lead_priority', $this->leadPriority),
            )
            ->when(
                $this->salesId !== '',
                function ($query): void {
                    if ($this->salesId === 'mine') {
                        $query->where('appointments.assigned_to', auth()->id());
                    } elseif ($this->salesId === 'unassigned') {
                        $query->whereNull('appointments.assigned_to');
                    } elseif (is_numeric($this->salesId)) {
                        $query->where('appointments.assigned_to', (int) $this->salesId);
                    }
                },
            )
            ->when(
                $rangeStart !== null && $rangeEnd !== null,
                fn ($query) => $query->whereBetween('appointments.appointment_date', [$rangeStart, $rangeEnd]),
            )
            ->when(
                $rangeStart !== null && $rangeEnd === null,
                fn ($query) => $query->where('appointments.appointment_date', '>=', $rangeStart),
            )
            ->when(
                $rangeStart === null && $rangeEnd !== null,
                fn ($query) => $query->where('appointments.appointment_date', '<=', $rangeEnd),
            )
            ->when(
                $keyword !== '',
                function ($query) use ($keyword): void {
                    $query->where(function ($subQuery) use ($keyword): void {
                        if (is_numeric(ltrim($keyword, '#'))) {
                            $subQuery->orWhere('appointments.id', (int) ltrim($keyword, '#'));
                        }

                        $subQuery->orWhere('appointments.customer_order_code', 'like', "%{$keyword}%")
                            ->orWhere('appointments.daily_sequence_code', 'like', "%{$keyword}%")
                            ->orWhere('appointments.customer_internal_code', 'like', "%{$keyword}%")
                            ->orWhere('appointments.guest_name', 'like', "%{$keyword}%")
                            ->orWhere('appointments.guest_email', 'like', "%{$keyword}%")
                            ->orWhere('appointments.guest_phone', 'like', "%{$keyword}%")
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
                            ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$keyword}%"));
                    });
                },
            )
            ->orderByDesc('appointments.created_at')
            ->orderByDesc('appointments.id')
            ->paginate(15);

        $activeFilterCount = collect([
            $keyword !== '',
            $this->status !== '',
            $this->type !== '',
            $this->leadPriority !== '',
            $this->salesId !== '',
            $this->datePreset !== '' || $this->onlyToday || $this->dateFrom !== '' || $this->dateTo !== '',
        ])->filter()->count();

        return view('livewire.admin.appointment-table', [
            'appointments' => $appointments,
            'statuses' => AppointmentStatus::cases(),
            'types' => AppointmentType::cases(),
            'salesRoster' => $salesRoster,
            'activeFilterCount' => $activeFilterCount,
            'quickStats' => [
                'pending' => (int) ($statsRow->pending_count ?? 0),
                'hot' => (int) ($statsRow->hot_count ?? 0),
                'today' => (int) ($statsRow->today_count ?? 0),
                'completed' => (int) ($statsRow->completed_count ?? 0),
                'archived' => (int) ($statsRow->archived_count ?? 0),
            ],
        ]);
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
