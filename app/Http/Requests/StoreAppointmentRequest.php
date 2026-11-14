<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isGuest = ! auth()->check();
        $type = $this->input('type');
        $isServiceType = in_array($type, ['maintenance', 'detailing', 'car_wash'], true);
        $isProductOptional = in_array($type, ['maintenance', 'detailing', 'car_wash', 'trade_in', 'consult', 'advisor_meeting'], true);

        return [
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:20', 'regex:/^[0-9+\s().-]{8,20}$/'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'product_id' => [$isProductOptional ? 'nullable' : 'required', 'nullable', 'exists:products,id'],
            'type' => ['required', Rule::in(['test_drive', 'viewing', 'quote', 'maintenance', 'detailing', 'car_wash', 'consult', 'advisor_meeting', 'trade_in'])],
            'appointment_date' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'meta_data' => ['nullable', 'array'],
            'meta_data.*' => ['nullable', 'string', 'max:500'],
            'showroom' => ['nullable', 'string', 'max:255'],
            'ai_visitor_id' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'customer_car_model' => [$isServiceType ? 'required' : 'nullable', 'string', 'max:255'],
            'customer_car_condition' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $meta = $this->input('meta_data');
        $sanitizedMeta = [];

        if (is_array($meta)) {
            foreach ($meta as $key => $value) {
                if (is_string($value)) {
                    $sanitizedMeta[$key] = trim(strip_tags($value));
                } elseif (is_int($value) || is_float($value)) {
                    $sanitizedMeta[$key] = (string) $value;
                }
            }
        }

        $type = $this->input('type');
        if ($type === 'service') {
            $type = 'maintenance';
            $sanitizedMeta['service_type'] = $sanitizedMeta['service_type'] ?? 'service';
        }

        $payload = [];

        if ($type !== null) {
            $payload['type'] = $type;
        }

        if (is_array($meta) || $this->input('type') === 'service') {
            $payload['meta_data'] = $sanitizedMeta;
        }

        foreach (['guest_name', 'notes', 'showroom', 'customer_car_model', 'customer_car_condition'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $payload[$field] = trim(strip_tags((string) $this->input($field)));
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('product_id')) {
                return;
            }

            $product = Product::query()
                ->select(['id', 'type', 'is_active'])
                ->find($this->input('product_id'));

            if (! $product) {
                return;
            }

            if (! (bool) $product->is_active) {
                $validator->errors()->add('product_id', 'Sản phẩm hiện không khả dụng để đặt lịch.');

                return;
            }

            if (! $product->canTestDrive()) {
                $validator->errors()->add('product_id', 'Phụ kiện không dùng luồng lái thử hoặc xem xe.');
            }
        });
    }
}
