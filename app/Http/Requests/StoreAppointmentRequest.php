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
        $isServiceType = in_array($this->input('type'), ['maintenance', 'detailing', 'car_wash']);
        $isTradeIn = $this->input('type') === 'trade_in';

        return [
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:20'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'product_id' => [($isServiceType || $isTradeIn) ? 'nullable' : 'required', 'nullable', 'exists:products,id'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('product_id')) {
                return;
            }

            $product = Product::query()
                ->select(['id', 'type'])
                ->find($this->input('product_id'));

            if (! $product) {
                return;
            }

            if (
                ! $product->canTestDrive()
                && in_array($this->input('type'), ['test_drive', 'viewing'], true)
            ) {
                $validator->errors()->add('product_id', 'Phụ kiện không dùng luồng lái thử hoặc xem xe.');
            }
        });
    }
}
