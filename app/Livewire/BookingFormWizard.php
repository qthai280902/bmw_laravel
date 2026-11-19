<?php

namespace App\Livewire;

use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class BookingFormWizard extends Component
{
    #[Url(as: 'type')]
    public string $type = AppointmentType::TestDrive->value;

    public string $categoryType = '';

    #[Url(as: 'product_id', except: '')]
    public string $productId = '';

    public string $guestName = '';

    public string $guestPhone = '';

    public string $guestEmail = '';

    public string $customerCarModel = '';

    public string $customerCarCondition = '';

    #[Url(as: 'service_package', except: '')]
    public string $servicePackage = '';

    public string $appointmentDate = '';

    public string $notes = '';

    /**
     * @var array<string, string>
     */
    private const PROPERTY_TO_FIELD_MAP = [
        'type' => 'type',
        'productId' => 'product_id',
        'guestName' => 'guest_name',
        'guestPhone' => 'guest_phone',
        'guestEmail' => 'guest_email',
        'customerCarModel' => 'customer_car_model',
        'customerCarCondition' => 'customer_car_condition',
        'servicePackage' => 'service_package',
        'appointmentDate' => 'appointment_date',
        'notes' => 'notes',
    ];

    public function mount(): void
    {
        $rawType = old('type', request('type', $this->type));
        $normalizedType = match ($rawType) {
            'service' => AppointmentType::Maintenance->value,
            'consultation' => AppointmentType::Consult->value,
            default => (string) $rawType,
        };

        if (AppointmentType::tryFrom($normalizedType) !== null) {
            $this->type = $normalizedType;
        }

        $this->guestName = (string) old('guest_name', '');
        $this->guestPhone = (string) old('guest_phone', '');
        $this->guestEmail = (string) old('guest_email', '');
        $this->customerCarModel = (string) old('customer_car_model', '');
        $this->customerCarCondition = (string) old('customer_car_condition', '');
        $this->servicePackage = (string) old('service_package', request('service_package', $this->servicePackage));
        $this->appointmentDate = (string) old('appointment_date', '');
        $this->notes = (string) old('notes', '');

        $initialProductId = old('product_id', request('product_id', $this->productId));
        if (is_numeric($initialProductId) && (int) $initialProductId > 0) {
            $product = Product::query()
                ->active()
                ->vehicles()
                ->select(['id', 'type'])
                ->find((int) $initialProductId);

            if ($product) {
                $this->productId = (string) $product->id;
                $this->categoryType = $product->type === VehicleType::MOTORBIKE ? 'xe_may' : 'oto';
            }
        }
    }

    public function updatedCategoryType(): void
    {
        $this->productId = '';
        $this->resetValidation('productId');
    }

    public function updatedType(string $value): void
    {
        if ($value === 'service') {
            $this->type = AppointmentType::Maintenance->value;
        } elseif ($value === 'consultation') {
            $this->type = AppointmentType::Consult->value;
        }

        $this->resetValidation(['productId', 'customerCarModel']);
    }

    public function updated(string $property): void
    {
        if (! array_key_exists($property, self::PROPERTY_TO_FIELD_MAP)) {
            return;
        }

        $field = self::PROPERTY_TO_FIELD_MAP[$property];
        $rules = StoreAppointmentRequest::sharedRules(! auth()->check(), $this->type);

        if (! isset($rules[$field])) {
            return;
        }

        $this->resetValidation($property);

        $validator = Validator::make(
            [$field => $this->formData()[$field] ?? null],
            [$field => $rules[$field]],
            StoreAppointmentRequest::sharedMessages(),
            StoreAppointmentRequest::sharedAttributes(),
        );

        if ($validator->fails()) {
            $this->addError($property, (string) $validator->errors()->first($field));
        }
    }

    #[Computed]
    public function isService(): bool
    {
        return in_array($this->type, [
            AppointmentType::Maintenance->value,
            AppointmentType::Detailing->value,
            AppointmentType::CarWash->value,
        ], true);
    }

    #[Computed]
    public function isProductOptional(): bool
    {
        return in_array($this->type, [
            AppointmentType::Maintenance->value,
            AppointmentType::Detailing->value,
            AppointmentType::CarWash->value,
            AppointmentType::TradeIn->value,
            AppointmentType::Consult->value,
            AppointmentType::AdvisorMeeting->value,
        ], true);
    }

    #[Computed]
    public function availableProducts(): Collection
    {
        if ($this->categoryType === '') {
            return new Collection;
        }

        $vehicleType = match ($this->categoryType) {
            'oto' => VehicleType::CAR->value,
            'xe_may' => VehicleType::MOTORBIKE->value,
            default => null,
        };

        if ($vehicleType === null) {
            return new Collection;
        }

        return Product::query()
            ->active()
            ->where('type', $vehicleType)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type', 'price', 'deposit_amount', 'category_id']);
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        if (! is_numeric($this->productId) || (int) $this->productId <= 0) {
            return null;
        }

        return Product::query()
            ->active()
            ->vehicles()
            ->with(['category', 'primaryImage', 'images'])
            ->find((int) $this->productId);
    }

    public function render(): View
    {
        $requestedType = AppointmentType::tryFrom($this->type);

        return view('livewire.booking-form-wizard', [
            'requestedType' => $requestedType,
            'servicePackageOptions' => Appointment::servicePackageOptions(),
            'salesTypes' => [
                AppointmentType::TestDrive,
                AppointmentType::Viewing,
                AppointmentType::Quote,
                AppointmentType::Consult,
                AppointmentType::AdvisorMeeting,
                AppointmentType::TradeIn,
            ],
            'serviceTypes' => [
                AppointmentType::Maintenance,
                AppointmentType::Detailing,
                AppointmentType::CarWash,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'type' => $this->type,
            'product_id' => $this->productId !== '' ? $this->productId : null,
            'guest_name' => $this->guestName,
            'guest_phone' => $this->guestPhone,
            'guest_email' => $this->guestEmail !== '' ? $this->guestEmail : null,
            'customer_car_model' => $this->customerCarModel,
            'customer_car_condition' => $this->customerCarCondition,
            'service_package' => $this->servicePackage !== '' ? $this->servicePackage : null,
            'appointment_date' => $this->appointmentDate,
            'notes' => $this->notes,
        ];
    }
}
