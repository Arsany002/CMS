<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class updateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'clinic_id' => ['sometimes', 'required', 'exists:clinics,id'],
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password'  => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'phone'     => ['sometimes', 'nullable', 'string', 'max:20'],
            'role'      => ['sometimes', 'required', new Enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
