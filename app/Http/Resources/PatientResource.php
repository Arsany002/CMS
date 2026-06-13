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
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender'        => $this->gender,
            'address'       => $this->address,

            // Conditionally Loaded Relationships
            'clinic'       => $this->whenLoaded('clinic', fn() => new ClinicResource($this->clinic)),
            'appointments' => $this->when(
                $this->relationLoaded('appointments'),
                fn() => AppointmentResource::collection($this->appointments)
            ),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
