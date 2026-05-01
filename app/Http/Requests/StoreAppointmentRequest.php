<?php

namespace App\Http\Requests;

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

        return [
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:20'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'product_id' => [$this->input('type') === 'trade_in' ? 'nullable' : 'required', 'nullable', 'exists:products,id'],
            'type' => ['required', Rule::in(['test_drive', 'viewing', 'quote', 'maintenance', 'detailing', 'car_wash', 'consult', 'advisor_meeting', 'trade_in'])],
            'appointment_date' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'meta_data' => ['nullable', 'array'],
            'meta_data.*' => ['nullable', 'string', 'max:500'],
            'showroom' => ['nullable', 'string', 'max:255'],
        ];
    }
}
