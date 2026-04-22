<?php

namespace App\Http\Requests;

use App\Enums\AppointmentType;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
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
        return [
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', Rule::enum(AppointmentType::class)],
            'appointment_date' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom validation after rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->type === AppointmentType::Maintenance->value) {
                // Kiểm tra user đã mua xe này chưa (Order status Paid)
                $owned = OrderItem::whereHas('order', function ($query) {
                    $query->where('user_id', $this->user()->id)
                        ->where('status', OrderStatus::Paid);
                })
                    ->where('product_id', $this->product_id)
                    ->exists();

                if (! $owned) {
                    $validator->errors()->add(
                        'product_id',
                        'Bạn chỉ có thể đặt lịch bảo dưỡng cho xe đã mua (đã thanh toán đặt cọc thành công).'
                    );
                }
            }
        });
    }
}
