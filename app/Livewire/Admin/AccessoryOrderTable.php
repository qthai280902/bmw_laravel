<?php

namespace App\Livewire\Admin;

use App\Exceptions\InvalidStatusTransitionException;
use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\HandlesCrmArchiveActions;
use App\Livewire\Concerns\WithBmwPagination;
use App\Models\AccessoryOrder;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\SalesAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class AccessoryOrderTable extends Component
{
    use AuthorizesAdminLivewire;
    use HandlesCrmArchiveActions;
    use WithBmwPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

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

    public ?int $inspectedOrderId = null;

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
            $isActive = $this->status === AccessoryOrder::STATUS_PENDING && ! $this->onlyToday && $this->datePreset === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AccessoryOrder::STATUS_PENDING;
            }

            return;
        }

        if ($preset === 'confirmed') {
            $isActive = $this->status === AccessoryOrder::STATUS_CONFIRMED && ! $this->onlyToday && $this->datePreset === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AccessoryOrder::STATUS_CONFIRMED;
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
            $isActive = $this->status === AccessoryOrder::STATUS_COMPLETED && ! $this->onlyToday && $this->datePreset === '';
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AccessoryOrder::STATUS_COMPLETED;
            }

            return;
        }

        $this->resetFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->salesId = '';
        $this->datePreset = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->onlyToday = false;
        $this->resetPage();
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag(['status', 'statusTransition']);
        $this->errorMessage = null;

        $validator = Validator::make(
            ['status' => $status],
            ['status' => ['required', 'string', Rule::in(AccessoryOrder::statusValues())]],
            ['status.*' => 'Trạng thái đơn phụ kiện không hợp lệ hoặc sai định dạng.'],
        );

        if ($validator->fails()) {
            $message = (string) $validator->errors()->first('status');
            $this->flashMessage = null;
            $this->errorMessage = $message;
            $this->addError('status', $message);
            $this->addError('statusTransition', $message);

            return;
        }

        $order = AccessoryOrder::query()->findOrFail($orderId);
        $nextStatus = (string) $validator->validated()['status'];

        try {
            $transitioned = $order->transitionToStatus($nextStatus, null, false, auth()->user());

            if (! $transitioned) {
                $staleMessage = "Đơn phụ kiện #{$order->id} hiện đã ở trạng thái '{$order->statusLabel()}' trước đó (có thể đã được nhân sự khác xử lý).";
                $this->flashMessage = null;
                $this->errorMessage = $staleMessage;
                $this->addError('statusTransition', $staleMessage);

                return;
            }

            $this->flashMessage = "Đơn phụ kiện #{$order->id} đã được cập nhật trạng thái.";
        } catch (InvalidStatusTransitionException $exception) {
            $this->flashMessage = null;
            $this->errorMessage = $exception->getMessage();
            $this->addError('statusTransition', $exception->getMessage());
        }
    }

    public function assignToSales(int $orderId, string|int|null $salesUserId, SalesAssignmentService $assignmentService): void
    {
        $this->authorizeSystemManagement();
        $this->resetErrorBag('statusTransition');
        $this->errorMessage = null;

        $order = AccessoryOrder::query()->findOrFail($orderId);
        $targetId = filled($salesUserId) ? (int) $salesUserId : null;

        try {
            $assignedUser = $assignmentService->assignManually($order, $targetId, auth()->user());
            unset($this->inspectedOrder);

            $this->flashMessage = $assignedUser
                ? "Đã phân công đơn {$order->customer_order_code} cho {$assignedUser->name}."
                : "Đã gỡ phân công Sales cho đơn {$order->customer_order_code}.";
        } catch (\Throwable $exception) {
            $this->flashMessage = null;
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function archiveRecord(int $orderId): void
    {
        $this->authorizeTopSuperAdmin();
        $order = AccessoryOrder::query()->findOrFail($orderId);
        $this->performArchive($order, 'đơn phụ kiện');
        unset($this->inspectedOrder);
    }

    public function unarchiveRecord(int $orderId): void
    {
        $this->authorizeTopSuperAdmin();
        $order = AccessoryOrder::query()->findOrFail($orderId);
        $this->performUnarchive($order, 'đơn phụ kiện');
        unset($this->inspectedOrder);
    }

    public function archiveAllClosed(ShowroomCacheService $cacheService): void
    {
        $this->authorizeTopSuperAdmin();
        $this->errorMessage = null;

        $count = AccessoryOrder::query()
            ->activeRecords()
            ->whereIn('status', [AccessoryOrder::STATUS_COMPLETED, AccessoryOrder::STATUS_CANCELLED])
            ->toBase()
            ->update([
                'archived_at' => now(),
                'archived_by' => auth()->id(),
            ]);

        $cacheService->flushDashboard();
        $this->resetPage();
        $this->flashMessage = $count > 0
            ? "Đã chuyển {$count} đơn phụ kiện đã đóng vào Kho lưu trữ CRM."
            : 'Không có đơn phụ kiện đã đóng nào cần lưu trữ thêm.';
    }

    public function inspectOrder(int $orderId): void
    {
        $this->authorizeCrm();
        $this->resetErrorBag('statusTransition');
        $this->errorMessage = null;

        $this->inspectedOrderId = $orderId;
    }

    public function closeInspector(): void
    {
        $this->inspectedOrderId = null;
    }

    #[Computed]
    public function inspectedOrder(): ?AccessoryOrder
    {
        if ($this->inspectedOrderId === null) {
            return null;
        }

        return AccessoryOrder::query()
            ->with(['product.category', 'assignedSales', 'archivedBy:id,name'])
            ->find($this->inspectedOrderId);
    }

    public function render(ShowroomCacheService $cacheService): View
    {
        $statusOptions = AccessoryOrder::statuses();
        $keyword = trim($this->search);
        $normalizedStatus = in_array($this->status, AccessoryOrder::statusValues(), true) ? $this->status : '';
        $todayStart = now()->startOfDay()->toDateTimeString();
        $todayEnd = now()->endOfDay()->toDateTimeString();
        [$rangeStart, $rangeEnd] = $this->resolveDateRange();

        $statsRow = AccessoryOrder::query()
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as confirmed_count,
                 SUM(CASE WHEN archived_at IS NULL AND created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN archived_at IS NULL AND status = ? THEN 1 ELSE 0 END) as completed_count,
                 SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) as archived_count',
                [
                    AccessoryOrder::STATUS_PENDING,
                    AccessoryOrder::STATUS_CONFIRMED,
                    $todayStart,
                    $todayEnd,
                    AccessoryOrder::STATUS_COMPLETED,
                ]
            )
            ->first();

        $salesRoster = $cacheService->salesRoster();

        $orders = AccessoryOrder::query()
            ->leftJoin('users as assigned_sales', 'accessory_orders.assigned_to', '=', 'assigned_sales.id')
            ->select('accessory_orders.*', 'assigned_sales.name as assigned_sales_name')
            ->with(['product:id,name,price,stock'])
            ->when(
                $this->archiveScope === 'archived',
                fn ($query) => $query->archived(),
                fn ($query) => $query->activeRecords(),
            )
            ->when(
                $normalizedStatus !== '',
                fn ($query) => $query->where('accessory_orders.status', $normalizedStatus),
            )
            ->when(
                $this->salesId !== '',
                function ($query): void {
                    if ($this->salesId === 'mine') {
                        $query->where('accessory_orders.assigned_to', auth()->id());
                    } elseif ($this->salesId === 'unassigned') {
                        $query->whereNull('accessory_orders.assigned_to');
                    } elseif (is_numeric($this->salesId)) {
                        $query->where('accessory_orders.assigned_to', (int) $this->salesId);
                    }
                },
            )
            ->when(
                $rangeStart !== null && $rangeEnd !== null,
                fn ($query) => $query->whereBetween('accessory_orders.created_at', [$rangeStart, $rangeEnd]),
            )
            ->when(
                $rangeStart !== null && $rangeEnd === null,
                fn ($query) => $query->where('accessory_orders.created_at', '>=', $rangeStart),
            )
            ->when(
                $rangeStart === null && $rangeEnd !== null,
                fn ($query) => $query->where('accessory_orders.created_at', '<=', $rangeEnd),
            )
            ->when(
                $keyword !== '',
                function ($query) use ($keyword): void {
                    $query->where(function ($subQuery) use ($keyword): void {
                        if (is_numeric(ltrim($keyword, '#'))) {
                            $subQuery->orWhere('accessory_orders.id', (int) ltrim($keyword, '#'));
                        }

                        $subQuery->orWhere('accessory_orders.customer_order_code', 'like', "%{$keyword}%")
                            ->orWhere('accessory_orders.daily_sequence_code', 'like', "%{$keyword}%")
                            ->orWhere('accessory_orders.customer_internal_code', 'like', "%{$keyword}%")
                            ->orWhere('accessory_orders.customer_name', 'like', "%{$keyword}%")
                            ->orWhere('accessory_orders.customer_phone', 'like', "%{$keyword}%")
                            ->orWhere('accessory_orders.customer_email', 'like', "%{$keyword}%")
                            ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$keyword}%"));
                    });
                },
            )
            ->orderByDesc('accessory_orders.created_at')
            ->orderByDesc('accessory_orders.id')
            ->paginate(15);

        $activeFilterCount = collect([
            $keyword !== '',
            $normalizedStatus !== '',
            $this->salesId !== '',
            $this->datePreset !== '' || $this->onlyToday || $this->dateFrom !== '' || $this->dateTo !== '',
        ])->filter()->count();

        return view('livewire.admin.accessory-order-table', [
            'orders' => $orders,
            'statusOptions' => $statusOptions,
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
