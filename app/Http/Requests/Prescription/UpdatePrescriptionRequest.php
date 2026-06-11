<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'diagnosis'              => ['sometimes', 'required', 'string', 'max:1000'],
            'notes'                  => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items'                  => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.medicine_name'  => ['required_with:items', 'string', 'max:255'],
            'items.*.dosage'         => ['required_with:items', 'string', 'max:255'],
            'items.*.frequency'      => ['required_with:items', 'string', 'max:255'],
            'items.*.duration'       => ['required_with:items', 'string', 'max:255'],
            'items.*.notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}
