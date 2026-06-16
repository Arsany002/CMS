<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'doctor_id'   => $this->doctor_id,
            'patient_id'  => $this->patient_id,
            'clinic_id'   => $this->clinic_id,
            'diagnosis'   => $this->diagnosis,
            'notes'       => $this->notes,

            // Medication items (always included when repository loads them)
            'items' => PrescriptionItemResource::collection(
                $this->whenLoaded('items', fn() => $this->items, collect())
            ),

            // Relationships (conditionally loaded)
            'patient'     => $this->whenLoaded('patient', fn() => new PatientResource($this->patient)),
            'doctor'      => $this->whenLoaded('doctor',  fn() => new UserResource($this->doctor)),
            'appointment' => $this->whenLoaded('appointment', fn() => new AppointmentResource($this->appointment)),

            'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
