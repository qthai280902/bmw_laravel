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

    public ?int $inspectedAppointmentId = null;

    public ?string $flashMessage = null;

    public function mount(): void
    {
        $this->authorizeAdmin();
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->type = '';
        $this->leadPriority = '';
        $this->resetPage();
    }

    public function updateStatus(int $appointmentId, string $status): void
    {
        $this->authorizeAdmin();

        $validated = Validator::make(
            ['status' => $status],
            ['status' => ['required', Rule::enum(AppointmentStatus::class)]],
        )->validate();

        $appointment = Appointment::query()->findOrFail($appointmentId);
        $appointment->update([
            'status' => $validated['status'],
        ]);

        $this->flashMessage = "Đã cập nhật trạng thái lịch hẹn #{$appointment->id} thành công.";
    }

    public function inspectAiConversation(int $appointmentId): void
    {
        $this->authorizeAdmin();

        $this->inspectedAppointmentId = $appointmentId;
    }

    public function closeAiConversationModal(): void
    {
        $this->inspectedAppointmentId = null;
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
        ]);
    }
}
