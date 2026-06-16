<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            // Cast enum to its string value so the frontend always receives a plain string
            'role'       => $this->role instanceof \BackedEnum ? $this->role->value : $this->role,
            'is_active'  => $this->is_active,
            'clinic_id'  => $this->clinic_id,
            // Expose clinic name when the relation is loaded (e.g. /auth/me, user list)
            'clinic'     => $this->whenLoaded('clinic', fn() => [
                'id'   => $this->clinic->id,
                'name' => $this->clinic->name,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
