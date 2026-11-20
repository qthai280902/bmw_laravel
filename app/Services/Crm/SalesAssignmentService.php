<?php

namespace App\Services\Crm;

use App\Enums\AppointmentStatus;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Cache\ShowroomCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesAssignmentService
{
    public function __construct(
        private readonly AdminNotificationDispatcher $notificationDispatcher,
        private readonly ShowroomCacheService $cacheService,
    ) {}

    /**
     * Chọn nhân sự Sales đang có số lượng hồ sơ mở (pending + confirmed) ít nhất để phân bổ công bằng.
     */
    public function pickLeastLoadedSales(): ?User
    {
        $salesUsers = User::query()
            ->sales()
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'role']);

        if ($salesUsers->isEmpty()) {
            return null;
        }

        $openStatuses = [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value];

        $appointmentLoads = Appointment::query()
            ->select('assigned_to', DB::raw('COUNT(*) as total_open'))
            ->whereNotNull('assigned_to')
            ->whereIn('status', $openStatuses)
            ->groupBy('assigned_to')
            ->pluck('total_open', 'assigned_to');

        $orderLoads = AccessoryOrder::query()
            ->select('assigned_to', DB::raw('COUNT(*) as total_open'))
            ->whereNotNull('assigned_to')
            ->whereIn('status', [AccessoryOrder::STATUS_PENDING, AccessoryOrder::STATUS_CONFIRMED])
            ->groupBy('assigned_to')
            ->pluck('total_open', 'assigned_to');

        return $salesUsers
            ->sortBy(static function (User $user) use ($appointmentLoads, $orderLoads): array {
                $load = (int) ($appointmentLoads[$user->id] ?? 0) + (int) ($orderLoads[$user->id] ?? 0);

                return [$load, $user->id];
            })
            ->first();
    }

    /**
     * Tự động gán nhân sự Sales (saveQuietly để không kích hoạt lại observer updated).
     */
    public function autoAssignIfUnassigned(Appointment|AccessoryOrder $record): ?User
    {
        if ($record->assigned_to !== null) {
            return User::query()->find($record->assigned_to);
        }

        $sales = $this->pickLeastLoadedSales();

        if (! $sales) {
            return null;
        }

        $record->forceFill(['assigned_to' => $sales->id])->saveQuietly();
        $record->setRelation('assignedSales', $sales);

        return $sales;
    }

    /**
     * Admin chủ động giao hoặc chuyển giao hồ sơ xuống nhân sự Sales, ghi Timeline & báo chuông.
     */
    public function assignManually(
        Appointment|AccessoryOrder $record,
        ?int $salesUserId,
        User $actor,
    ): ?User {
        $previousSalesId = $record->assigned_to !== null ? (int) $record->assigned_to : null;
        $assignee = null;

        if ($salesUserId !== null) {
            $assignee = User::query()
                ->sales()
                ->findOrFail($salesUserId);
        }

        if ($previousSalesId === ($assignee?->id)) {
            return $assignee;
        }

        $meta = is_array($record->meta_data) ? $record->meta_data : [];
        $codeLabel = $record->customer_order_code ?: '#'.$record->id;
        $detail = $assignee
            ? "Chuyển giao hồ sơ {$codeLabel} cho chuyên viên Sales {$assignee->name}."
            : "Gỡ phân công chuyên viên phụ trách khỏi hồ sơ {$codeLabel}.";

        $meta = InteractionTimelineRecorder::appendToMeta(
            meta: $meta,
            actorType: InteractionTimelineRecorder::ACTOR_ADMIN,
            actorLabel: InteractionTimelineRecorder::adminActorLabel($actor),
            title: $assignee ? "Phân công Sales: {$assignee->name}" : 'Thu hồi phân công Sales',
            detail: $detail,
            channel: 'CRM',
        );

        $record->forceFill([
            'assigned_to' => $assignee?->id,
            'meta_data' => $meta,
        ])->saveQuietly();

        $record->setRelation('assignedSales', $assignee);
        $this->cacheService->flushDashboard();

        if ($assignee) {
            $this->notificationDispatcher->notifySalesLeadAssigned($record, $assignee, $actor);
        }

        return $assignee;
    }

    /**
     * Phân bổ đều toàn bộ hồ sơ chưa có người phụ trách xuống đội ngũ Sales hiện có.
     *
     * @return array{appointments: int, accessory_orders: int}
     */
    public function distributeUnassignedEvenly(?User $actor = null): array
    {
        /** @var Collection<int, User> $salesUsers */
        $salesUsers = User::query()
            ->sales()
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'role'])
            ->values();

        if ($salesUsers->isEmpty()) {
            return ['appointments' => 0, 'accessory_orders' => 0];
        }

        $salesCount = $salesUsers->count();
        $cursor = 0;
        $appointmentsAssigned = 0;
        $ordersAssigned = 0;

        Appointment::query()
            ->whereNull('assigned_to')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($salesUsers, $salesCount, &$cursor, &$appointmentsAssigned, $actor): void {
                foreach ($rows as $appointment) {
                    /** @var User $assignee */
                    $assignee = $salesUsers[$cursor % $salesCount];
                    $cursor++;

                    if ($actor) {
                        $this->assignManually($appointment, (int) $assignee->id, $actor);
                    } else {
                        $appointment->forceFill(['assigned_to' => $assignee->id])->saveQuietly();
                    }

                    $appointmentsAssigned++;
                }
            });

        AccessoryOrder::query()
            ->whereNull('assigned_to')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($salesUsers, $salesCount, &$cursor, &$ordersAssigned, $actor): void {
                foreach ($rows as $order) {
                    /** @var User $assignee */
                    $assignee = $salesUsers[$cursor % $salesCount];
                    $cursor++;

                    if ($actor) {
                        $this->assignManually($order, (int) $assignee->id, $actor);
                    } else {
                        $order->forceFill(['assigned_to' => $assignee->id])->saveQuietly();
                    }

                    $ordersAssigned++;
                }
            });

        $this->cacheService->flushDashboard();

        return [
            'appointments' => $appointmentsAssigned,
            'accessory_orders' => $ordersAssigned,
        ];
    }
}
