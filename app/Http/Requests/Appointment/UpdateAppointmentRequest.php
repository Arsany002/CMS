<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Notice: clinic_id and patient_id are intentionally missing. 
            // You should never "transfer" an existing appointment to a different patient or branch.

            // Allow reassigning to a different doctor (if the current one calls in sick)
            'doctor_id'        => ['sometimes', 'required', 'exists:users,id'],

            // Rescheduling rules
            'appointment_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
            'start_time'       => ['sometimes', 'required', 'date_format:H:i'],
            'end_time'         => ['sometimes', 'required', 'date_format:H:i', 'after:start_time'],

            // Status updates (ensure these match your exact Enums or database constraints)
            'status'           => ['sometimes', 'required', 'in:booked,confirmed,cancelled,completed,no_show'],

            // Notes can simply be appended or edited
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
