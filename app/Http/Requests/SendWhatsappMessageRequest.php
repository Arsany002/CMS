<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsappMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_phone' => ['required', 'string', 'regex:/^\+?\d{8,15}$/'],
            'message'  => ['required', 'string', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_phone.regex' => 'The phone number must be in E.164 format (optional +, 8–15 digits).',
        ];
    }
}
