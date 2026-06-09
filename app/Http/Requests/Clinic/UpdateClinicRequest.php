<?php

namespace App\Http\Requests\Clinic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicRequest extends FormRequest
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
        $clinic = $this->route('clinic');

        return [
            // 'sometimes' means this rule only runs if the frontend actually sent the 'name' field
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'address'      => ['sometimes', 'required', 'string', 'max:500'],
            'phone_number' => ['sometimes', 'required', 'string', 'max:20'],

            // The Email rule is the most important part of an Update request
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('clinics', 'email')->ignore($clinic)
            ],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
