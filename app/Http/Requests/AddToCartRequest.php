<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cart là public cho cả Guest
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Xe không tồn tại hoặc đã bị xóa.',
            'quantity.min' => 'Số lượng phải ít nhất là 1.',
            'quantity.max' => 'Số lượng tối đa mỗi lần là 10.',
        ];
    }
}
