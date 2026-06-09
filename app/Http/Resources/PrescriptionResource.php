<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
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


            // Core Prescription Data
            'diagnosis' => $this->diagnosis,
            'notes'     => $this->notes,

            // Conditionally Loaded Relationships
            'appointment' => $this->whenLoaded('appointment', fn() => new AppointmentResource($this->appointment)),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
