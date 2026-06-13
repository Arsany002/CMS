<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorSchedule>
 */
class DoctorScheduleFactory extends Factory
{
    protected $model = DoctorSchedule::class;

    public function definition(): array
    {
        return [
            'doctor_id'     => User::factory()->doctor(),
            'clinic_id'     => Clinic::factory(),
            'day_of_week'   => 1, // Monday (0=Sun … 6=Sat)
            'start_time'    => '08:00',
            'end_time'      => '17:00',
            'slot_duration' => 60,
            'is_active'     => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
