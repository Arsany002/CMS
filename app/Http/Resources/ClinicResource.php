<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicResource extends JsonResource
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

            // Core Clinic Data
            'name'         => $this->name,
            'address'      => $this->address,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'is_active'    => (bool) $this->is_active,

            // Conditionally Loaded Relationships (One-to-Many)
            'doctors' => UserResource::collection(
                $this->doctors ?? collect()
            ),
            'appointments' => AppointmentResource::collection(
                $this->appointments ?? collect()
            ),
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
