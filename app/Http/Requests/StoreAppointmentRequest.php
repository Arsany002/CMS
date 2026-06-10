<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage appointments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id'   => ['required', 'string', 'exists:patients,id'], // UUID
            'scheduled_at' => ['required', 'date', 'after:now'],
            'type'         => ['required', 'string', 'in:consultation,follow_up,emergency'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
