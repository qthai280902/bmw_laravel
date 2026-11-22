<?php

namespace App\Livewire\Concerns;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\InteractionTimelineRecorder;
use Livewire\Attributes\Url;

trait HandlesCrmArchiveActions
{
    #[Url(as: 'archive', except: 'active')]
    public string $archiveScope = 'active';

    public ?string $pendingArchiveAction = null;

    public ?int $pendingArchiveRecordId = null;

    public string $pendingArchiveReferenceCode = '';

    public function promptArchiveRecord(int $recordId, string $referenceCode = ''): void
    {
        $this->authorizeTopSuperAdmin();
        $this->pendingArchiveAction = 'archive';
        $this->pendingArchiveRecordId = $recordId;
        $this->pendingArchiveReferenceCode = $referenceCode;
    }

    public function promptUnarchiveRecord(int $recordId, string $referenceCode = ''): void
    {
        $this->authorizeTopSuperAdmin();
        $this->pendingArchiveAction = 'unarchive';
        $this->pendingArchiveRecordId = $recordId;
        $this->pendingArchiveReferenceCode = $referenceCode;
    }

    public function promptArchiveAllClosed(): void
    {
        $this->authorizeTopSuperAdmin();
        $this->pendingArchiveAction = 'archive_all_closed';
        $this->pendingArchiveRecordId = null;
        $this->pendingArchiveReferenceCode = '';
    }

    public function cancelArchiveConfirm(): void
    {
        $this->pendingArchiveAction = null;
        $this->pendingArchiveRecordId = null;
        $this->pendingArchiveReferenceCode = '';
    }

    public function confirmPendingArchiveAction(ShowroomCacheService $cacheService): void
    {
        $this->authorizeTopSuperAdmin();

        $action = $this->pendingArchiveAction;
        $recordId = $this->pendingArchiveRecordId;

        $this->cancelArchiveConfirm();

        if ($action === 'archive' && $recordId !== null && method_exists($this, 'archiveRecord')) {
            $this->archiveRecord($recordId);

            return;
        }

        if ($action === 'unarchive' && $recordId !== null && method_exists($this, 'unarchiveRecord')) {
            $this->unarchiveRecord($recordId);

            return;
        }

        if ($action === 'archive_all_closed' && method_exists($this, 'archiveAllClosed')) {
            $this->archiveAllClosed($cacheService);
        }
    }

    public function updatedArchiveScope(): void
    {
        $this->archiveScope = $this->archiveScope === 'archived' ? 'archived' : 'active';
        $this->resetPage();
    }

    public function setArchiveScope(string $scope): void
    {
        $this->authorizeCrm();
        $this->archiveScope = $scope === 'archived' ? 'archived' : 'active';
        $this->resetPage();
    }

    protected function authorizeTopSuperAdmin(): void
    {
        abort_unless((bool) auth()->user()?->isTopSuperAdmin(), 403);
    }

    protected function performArchive(Appointment|AccessoryOrder $record, string $entityLabel): void
    {
        $this->authorizeTopSuperAdmin();
        $this->errorMessage = null;

        try {
            $record->archive(auth()->user());
            $this->flashMessage = "Đã lưu trữ {$entityLabel} {$record->publicReferenceCode()} vào Kho lưu trữ CRM.";
        } catch (InvalidStatusTransitionException $exception) {
            $this->flashMessage = null;
            $this->errorMessage = $exception->getMessage();
        }
    }

    protected function performUnarchive(Appointment|AccessoryOrder $record, string $entityLabel): void
    {
        $this->authorizeTopSuperAdmin();
        $this->errorMessage = null;

        $record->unarchive(auth()->user());
        $this->flashMessage = "Đã khôi phục {$entityLabel} {$record->publicReferenceCode()} về danh sách CRM đang hoạt động.";
    }

    protected function acknowledgeAppointmentReschedule(Appointment $appointment, string $entityLabel): void
    {
        $this->authorizeCrm();
        $this->errorMessage = null;

        if ($appointment->isTerminalStatus()) {
            $this->flashMessage = null;
            $this->errorMessage = "Hồ sơ {$appointment->publicReferenceCode()} đã đóng ({$appointment->statusLabel()}), không thể tiếp nhận đổi lịch.";

            return;
        }

        $meta = InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);
        $meta['reschedule_pending_review'] = false;
        $meta['reschedule_acknowledged_at'] = now()->toIso8601String();
        $meta['reschedule_acknowledged_by'] = auth()->user()?->name ?? 'Chuyên viên Showroom';

        $actor = auth()->user();
        $newSlot = $appointment->appointment_date?->format('H:i — d/m/Y') ?? 'khung giờ mới';
        $meta = InteractionTimelineRecorder::appendToMeta(
            meta: $meta,
            actorType: InteractionTimelineRecorder::ACTOR_ADMIN,
            actorLabel: InteractionTimelineRecorder::adminActorLabel($actor),
            title: 'Xác nhận đã tiếp nhận lịch hẹn mới',
            detail: "Chuyên viên đã tiếp nhận thay đổi lịch sang {$newSlot} và phát hành liên kết quản lý phiên bản mới (v={$appointment->currentManageVersion()}).",
            channel: 'CRM',
        );

        $appointment->forceFill(['meta_data' => $meta])->saveQuietly();
        $appointment->markRescheduleNotificationsAsRead();

        $mailSent = $appointment->sendOfficialConfirmationEmail($actor);
        $this->flashMessage = $mailSent
            ? "Đã xác nhận tiếp nhận lịch hẹn mới và gửi lại thư xác nhận chính thức (v={$appointment->currentManageVersion()}) cho {$entityLabel} {$appointment->publicReferenceCode()}."
            : "Đã xác nhận tiếp nhận lịch hẹn mới cho {$entityLabel} {$appointment->publicReferenceCode()}.";
    }
}
