<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 1. Primary Identifier
            'id' => $this->id,

            // 2. Foreign Keys (Crucial for frontend state management)
            'clinic_id'  => $this->clinic_id,
            'patient_id' => $this->patient_id,
            'doctor_id'  => $this->doctor_id,

            // 3. Core Appointment Data
            'appointment_date' => $this->appointment_date->format('Y-m-d'),
            'start_time'       => $this->start_time,
            'end_time'         => $this->end_time,
            'status'           => $this->status->value ?? $this->status,
            'notes'            => $this->notes,

            // 4. Conditionally Loaded Relationships
            'clinic'       => $this->whenLoaded('clinic', fn() => new ClinicResource($this->clinic)),
            'patient'      => $this->whenLoaded('patient', fn() => new PatientResource($this->patient)),
            'doctor'       => $this->whenLoaded('doctor', fn() => new UserResource($this->doctor)),
            'prescription' => $this->whenLoaded('prescription', fn() => new PrescriptionResource($this->prescription)),

            // 5. Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
