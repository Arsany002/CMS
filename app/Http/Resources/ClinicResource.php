<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'address'   => $this->address,
            'phone'     => $this->phone,
            'email'     => $this->email,
            'is_active' => (bool) $this->is_active,

            // Only included when the relation was explicitly eager-loaded
            'doctors'      => $this->whenLoaded('doctors',      fn() => UserResource::collection($this->doctors)),
            'appointments' => $this->whenLoaded('appointments', fn() => AppointmentResource::collection($this->appointments)),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
