<?php

namespace App\Http\Requests;

use App\DTOs\UsageEventDTO;
use Illuminate\Foundation\Http\FormRequest;

class RecordUsageRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'event_id' => ['required_without:idempotency_key', 'nullable', 'string', 'max:128'],
            'idempotency_key' => ['required_without:event_id', 'nullable', 'string', 'max:128'],
            'units' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'event_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'timestamp' => ['sometimes', 'nullable', 'date'],
            'recorded_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'The customer_id field is required.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'event_id.required_without' => 'The event_id field is required when idempotency_key is not present.',
            'idempotency_key.required_without' => 'The idempotency_key field is required when event_id is not present.',
        ];
    }

    /**
     * Transform validated request payload into typed DTO.
     */
    public function toDTO(): UsageEventDTO
    {
        return UsageEventDTO::fromRequest($this->validated());
    }
}

