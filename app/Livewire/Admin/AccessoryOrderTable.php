<?php

namespace App\Livewire\Admin;

use App\Exceptions\InvalidStatusTransitionException;
use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\WithBmwPagination;
use App\Models\AccessoryOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class AccessoryOrderTable extends Component
{
    use AuthorizesAdminLivewire;
    use WithBmwPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'today', except: false)]
    public bool $onlyToday = false;

    public ?int $inspectedOrderId = null;

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

    public function applyQuickFilter(string $preset): void
    {
        $this->authorizeCrm();

        if ($preset === 'pending') {
            $isActive = $this->status === AccessoryOrder::STATUS_PENDING && ! $this->onlyToday;
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AccessoryOrder::STATUS_PENDING;
            }

            return;
        }

        if ($preset === 'confirmed') {
            $isActive = $this->status === AccessoryOrder::STATUS_CONFIRMED && ! $this->onlyToday;
            $this->resetFilters();
            if (! $isActive) {
                $this->status = AccessoryOrder::STATUS_CONFIRMED;
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
            $isActive = $this->status === AccessoryOrder::STATUS_COMPLETED && ! $this->onlyToday;
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
            ->with(['product.category'])
            ->find($this->inspectedOrderId);
    }

    public function render(): View
    {
        $statusOptions = AccessoryOrder::statuses();
        $keyword = trim($this->search);
        $normalizedStatus = in_array($this->status, AccessoryOrder::statusValues(), true) ? $this->status : '';
        $todayStart = now()->startOfDay()->toDateTimeString();
        $todayEnd = now()->endOfDay()->toDateTimeString();

        $statsRow = AccessoryOrder::query()
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed_count,
                 SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count',
                [
                    AccessoryOrder::STATUS_PENDING,
                    AccessoryOrder::STATUS_CONFIRMED,
                    $todayStart,
                    $todayEnd,
                    AccessoryOrder::STATUS_COMPLETED,
                ]
            )
            ->first();

        $orders = AccessoryOrder::query()
            ->with(['product.category'])
            ->when(
                $normalizedStatus !== '',
                fn ($query) => $query->where('status', $normalizedStatus),
            )
            ->when(
                $this->onlyToday,
                fn ($query) => $query->whereBetween('created_at', [$todayStart, $todayEnd]),
            )
            ->when(
                $keyword !== '',
                function ($query) use ($keyword): void {
                    $query->where(function ($subQuery) use ($keyword): void {
                        if (is_numeric(ltrim($keyword, '#'))) {
                            $subQuery->orWhere('id', (int) ltrim($keyword, '#'));
                        }

                        $subQuery->orWhere('customer_name', 'like', "%{$keyword}%")
                            ->orWhere('customer_phone', 'like', "%{$keyword}%")
                            ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$keyword}%"));
                    });
                },
            )
            ->latest()
            ->paginate(15);

        return view('livewire.admin.accessory-order-table', [
            'orders' => $orders,
            'statusOptions' => $statusOptions,
            'quickStats' => [
                'pending' => (int) ($statsRow->pending_count ?? 0),
                'confirmed' => (int) ($statsRow->confirmed_count ?? 0),
                'today' => (int) ($statsRow->today_count ?? 0),
                'completed' => (int) ($statsRow->completed_count ?? 0),
            ],
        ]);
    }
}
