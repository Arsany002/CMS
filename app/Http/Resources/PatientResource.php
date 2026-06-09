<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Primary Identifier
            'id' => $this->id,

            // Foreign Keys
            'clinic_id' => $this->clinic_id,

            // Core Patient Data
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'full_name'     => trim("{$this->first_name} {$this->last_name}"),
            'phone'         => $this->phone,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender'        => $this->gender,
            'blood_type'    => $this->blood_type,
            'notes'         => $this->notes,

            // Conditionally Loaded Relationships
            'clinic'        => $this->whenLoaded('clinic', fn() => new ClinicResource($this->clinic)),
            'appointments'  => AppointmentResource::collection($this->whenLoaded('appointments')),
            'prescriptions' => PrescriptionResource::collection($this->whenLoaded('prescriptions')),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
