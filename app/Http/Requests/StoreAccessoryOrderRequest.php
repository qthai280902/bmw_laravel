<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccessoryOrderRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::sharedRules();
    }

    /**
     * Shared accessory order validation rules for HTTP requests and Livewire calculator.
     *
     * @return array<string, mixed>
     */
    public static function sharedRules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s().-]{8,30}$/'],
            'customer_address' => ['required', 'string', 'max:500'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'ai_visitor_id' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if (! $this->has('quantity')) {
            $payload['quantity'] = 1;
        }

        $user = auth()->user();
        if ($user) {
            if (! $this->filled('customer_name') && filled($user->name)) {
                $payload['customer_name'] = $user->name;
            }
            if (! $this->filled('customer_email') && filled($user->email)) {
                $payload['customer_email'] = $user->email;
            }
        }

        foreach (['customer_name', 'customer_address', 'notes'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $payload[$field] = trim(strip_tags((string) $this->input($field)));
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
