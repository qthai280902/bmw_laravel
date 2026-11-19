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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = is_string($this->input('type')) ? $this->input('type') : null;

        return self::sharedRules(! auth()->check(), $type);
    }

    /**
     * Shared appointment validation rules for HTTP requests and Livewire wizard.
     *
     * @return array<string, mixed>
     */
    public static function sharedRules(bool $isGuest, ?string $type): array
    {
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
            'service_package' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Vietnamese human-friendly field labels for validation messages.
     *
     * @return array<string, string>
     */
    public static function sharedAttributes(): array
    {
        return [
            'guest_name' => 'họ và tên',
            'customer_name' => 'họ và tên',
            'guest_phone' => 'số điện thoại',
            'customer_phone' => 'số điện thoại',
            'guest_email' => 'email',
            'customer_email' => 'email',
            'product_id' => 'mẫu xe quan tâm',
            'type' => 'loại yêu cầu',
            'appointment_date' => 'ngày và giờ dự kiến',
            'notes' => 'ghi chú thêm',
            'showroom' => 'chi nhánh showroom',
            'customer_car_model' => 'dòng xe hiện tại',
            'customer_car_condition' => 'biển số / tình trạng xe',
            'service_package' => 'gói dịch vụ chăm sóc xe',
        ];
    }

    /**
     * Natural Vietnamese validation messages for booking forms.
     *
     * @return array<string, string>
     */
    public static function sharedMessages(): array
    {
        return [
            'appointment_date.required' => 'Vui lòng chọn ngày và giờ dự kiến.',
            'appointment_date.date' => 'Ngày và giờ dự kiến chưa đúng định dạng hợp lệ.',
            'appointment_date.after' => 'Ngày và giờ dự kiến phải sau thời điểm hiện tại.',
            'guest_name.required' => 'Vui lòng nhập họ và tên của Quý khách.',
            'guest_phone.required' => 'Vui lòng nhập số điện thoại liên hệ.',
            'guest_phone.regex' => 'Số điện thoại liên hệ chưa đúng định dạng hợp lệ.',
            'guest_email.email' => 'Địa chỉ email chưa đúng định dạng hợp lệ.',
            'product_id.required' => 'Vui lòng chọn mẫu xe quan tâm.',
            'customer_car_model.required' => 'Vui lòng nhập tên dòng xe Quý khách cần chăm sóc.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::sharedAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::sharedMessages();
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
        } elseif ($type === 'consultation') {
            $type = 'consult';
        }

        if ($this->filled('service_package') && is_string($this->input('service_package'))) {
            $cleanPackage = trim(strip_tags((string) $this->input('service_package')));
            if ($cleanPackage !== '') {
                $sanitizedMeta['service_package'] = $cleanPackage;
            }
        }

        $payload = [];

        if ($type !== null) {
            $payload['type'] = $type;
        }

        if (is_array($meta) || $this->input('type') === 'service' || isset($sanitizedMeta['service_package'])) {
            $payload['meta_data'] = $sanitizedMeta;
        }

        foreach (['guest_name', 'notes', 'showroom', 'customer_car_model', 'customer_car_condition', 'service_package'] as $field) {
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
