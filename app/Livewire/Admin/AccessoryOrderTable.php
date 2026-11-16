<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AuthorizesAdminLivewire;
use App\Livewire\Concerns\WithBmwPagination;
use App\Models\AccessoryOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $this->authorizeAdmin();

        $validated = Validator::make(
            ['status' => $status],
            ['status' => ['required', 'string', Rule::in(AccessoryOrder::statusValues())]],
        )->validate();

        $order = AccessoryOrder::query()->findOrFail($orderId);
        $order->transitionToStatus($validated['status']);

        $this->flashMessage = "Đơn phụ kiện #{$order->id} đã được cập nhật trạng thái.";
    }

    public function render(): View
    {
        $statusOptions = AccessoryOrder::statuses();
        $keyword = trim($this->search);
        $normalizedStatus = in_array($this->status, AccessoryOrder::statusValues(), true) ? $this->status : '';

        $orders = AccessoryOrder::query()
            ->with(['product.category'])
            ->when(
                $normalizedStatus !== '',
                fn ($query) => $query->where('status', $normalizedStatus),
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
        ]);
    }
}
