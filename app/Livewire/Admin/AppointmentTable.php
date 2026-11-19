<?php

namespace App\Livewire\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\WithBmwPagination;
use App\Models\AiChatSession;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class AppointmentTable extends Component
{
    use AuthorizesAdminLivewire;
    use WithBmwPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'priority', except: '')]
    public string $leadPriority = '';

    #[Url(as: 'today', except: false)]
    public bool $onlyToday = false;

    public ?int $inspectedAppointmentId = null;

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

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedLeadPriority(): void
    {
        $this->resetPage();
    }

    public function applyQuickFilter(string $preset): void
    {
        $this->authorizeCrm();

        if ($preset === 'pending') {
            $isActive = $this->status === AppointmentStatus::Pending->value && $this->leadPriority === '' && ! $this->onlyToday;
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AppointmentStatus::Pending->value;
            }

            return;
        }

        if ($preset === 'hot') {
            $isActive = $this->leadPriority === Appointment::LEAD_PRIORITY_HOT && $this->status === '' && ! $this->onlyToday;
            $this->resetFilters();
            if (! $isActive) {
                $this->leadPriority = Appointment::LEAD_PRIORITY_HOT;
            }

            return;
        }

        if ($preset === 'today') {
            $isActive = $this->onlyToday && $this->status === '' && $this->leadPriority === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->onlyToday = true;
            }

            return;
        }

        if ($preset === 'completed') {
            $isActive = $this->status === AppointmentStatus::Completed->value && $this->leadPriority === '' && ! $this->onlyToday;
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
        $this->onlyToday = false;
        $this->resetPage();
    }

    public function updateStatus(int $appointmentId, string $status): void
    {
        $this->authorizeCrm();

        $validated = Validator::make(
            ['status' => $status],
            ['status' => ['required', Rule::enum(AppointmentStatus::class)]],
        )->validate();

        $appointment = Appointment::query()->findOrFail($appointmentId);
        $appointment->transitionStatusWithAutomation(
            AppointmentStatus::from($validated['status']),
            auth()->user()
        );

        $this->flashMessage = "Đã cập nhật trạng thái lịch hẹn #{$appointment->id} thành công.";
    }

    public function resendConfirmationEmail(int $appointmentId): void
    {
        $this->authorizeCrm();

        $appointment = Appointment::query()
            ->with(['product.category', 'user'])
            ->findOrFail($appointmentId);

        $recipientEmail = trim((string) ($appointment->user?->email ?? $appointment->guest_email ?? ''));

        if ($recipientEmail === '') {
            $this->flashMessage = "Lịch hẹn #{$appointment->id} chưa có địa chỉ email để gửi thư xác nhận.";

            return;
        }

        if ($appointment->sendOfficialConfirmationEmail(auth()->user())) {
            $this->flashMessage = "Đã gửi thư Xác nhận chính thức từ Showroom tới {$recipientEmail} cho lịch hẹn #{$appointment->id}.";
        } else {
            $this->flashMessage = "Không thể gửi thư xác nhận chính thức cho lịch hẹn #{$appointment->id}.";
        }
    }

    public function inspectAppointment(int $appointmentId): void
    {
        $this->authorizeCrm();

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
            ->with(['user', 'product', 'aiChatSession.messages'])
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

    public function render(): View
    {
        $keyword = trim($this->search);
        $todayStart = now()->startOfDay()->toDateTimeString();
        $todayEnd = now()->endOfDay()->toDateTimeString();

        // Single aggregate SQL query for contextual quick-filter strip (keeps <= 8 total queries)
        $statsRow = Appointment::query()
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN lead_priority = ? THEN 1 ELSE 0 END) as hot_count,
                 SUM(CASE WHEN appointment_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count',
                [
                    AppointmentStatus::Pending->value,
                    Appointment::LEAD_PRIORITY_HOT,
                    $todayStart,
                    $todayEnd,
                    AppointmentStatus::Completed->value,
                ]
            )
            ->first();

        $appointments = Appointment::query()
            ->with(['user', 'product'])
            ->when(
                $this->status !== '',
                fn ($query) => $query->where('status', $this->status),
            )
            ->when(
                $this->type !== '',
                fn ($query) => $query->where('type', $this->type),
            )
            ->when(
                in_array($this->leadPriority, [Appointment::LEAD_PRIORITY_HOT, Appointment::LEAD_PRIORITY_WARM, Appointment::LEAD_PRIORITY_NORMAL], true),
                fn ($query) => $query->where('lead_priority', $this->leadPriority),
            )
            ->when(
                $this->onlyToday,
                fn ($query) => $query->whereBetween('appointment_date', [$todayStart, $todayEnd]),
            )
            ->when(
                $keyword !== '',
                function ($query) use ($keyword): void {
                    $query->where(function ($subQuery) use ($keyword): void {
                        $subQuery->where('guest_name', 'like', "%{$keyword}%")
                            ->orWhere('guest_email', 'like', "%{$keyword}%")
                            ->orWhere('guest_phone', 'like', "%{$keyword}%")
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
                            ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$keyword}%"));
                    });
                },
            )
            ->latest()
            ->paginate(15);

        return view('livewire.admin.appointment-table', [
            'appointments' => $appointments,
            'statuses' => AppointmentStatus::cases(),
            'types' => AppointmentType::cases(),
            'quickStats' => [
                'pending' => (int) ($statsRow->pending_count ?? 0),
                'hot' => (int) ($statsRow->hot_count ?? 0),
                'today' => (int) ($statsRow->today_count ?? 0),
                'completed' => (int) ($statsRow->completed_count ?? 0),
            ],
        ]);
    }
}
