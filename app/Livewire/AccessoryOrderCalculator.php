<?php

namespace App\Livewire;

use App\Http\Requests\StoreAccessoryOrderRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AccessoryOrderCalculator extends Component
{
    public Product $product;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $customerAddress = '';

    public string $customerEmail = '';

    public int $quantity = 1;

    public string $notes = '';

    /**
     * @var array<string, string>
     */
    private const PROPERTY_TO_FIELD_MAP = [
        'customerName' => 'customer_name',
        'customerPhone' => 'customer_phone',
        'customerAddress' => 'customer_address',
        'customerEmail' => 'customer_email',
        'quantity' => 'quantity',
        'notes' => 'notes',
    ];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->customerName = (string) old('customer_name', auth()->user()?->name ?? '');
        $this->customerPhone = (string) old('customer_phone', '');
        $this->customerAddress = (string) old('customer_address', '');
        $this->customerEmail = (string) old('customer_email', auth()->user()?->email ?? '');
        $this->quantity = max(1, (int) old('quantity', 1));
        $this->notes = (string) old('notes', '');
    }

    public function incrementQuantity(): void
    {
        $this->quantity = min(99, max(1, $this->quantity) + 1);
        $this->validateSingleField('quantity');
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
        $this->validateSingleField('quantity');
    }

    public function updatedQuantity(mixed $value): void
    {
        $parsed = is_numeric($value) ? (int) $value : 1;
        $this->quantity = max(1, min(99, $parsed));
    }

    public function updated(string $property): void
    {
        $this->validateSingleField($property);
    }

    #[Computed]
    public function totalAmount(): int
    {
        return (int) $this->product->price * max(1, $this->quantity);
    }

    public function render(): View
    {
        return view('livewire.accessory-order-calculator');
    }

    private function validateSingleField(string $property): void
    {
        if (! array_key_exists($property, self::PROPERTY_TO_FIELD_MAP)) {
            return;
        }

        $field = self::PROPERTY_TO_FIELD_MAP[$property];
        $rules = StoreAccessoryOrderRequest::sharedRules();

        if (! isset($rules[$field])) {
            return;
        }

        $this->resetValidation($property);

        $data = [
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'customer_address' => $this->customerAddress,
            'customer_email' => $this->customerEmail !== '' ? $this->customerEmail : null,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
        ];

        $validator = Validator::make(
            [$field => $data[$field]],
            [$field => $rules[$field]],
        );

        if ($validator->fails()) {
            $this->addError($property, (string) $validator->errors()->first($field));
        }
    }
}
