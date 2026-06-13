<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'doctor_id'     => $this->doctor_id,
            'clinic_id'     => $this->clinic_id,
            'day_of_week'   => $this->day_of_week,
            'start_time'    => $this->start_time,
            'end_time'      => $this->end_time,
            'slot_duration' => $this->slot_duration,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
