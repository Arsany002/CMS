<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $clinic    = Clinic::factory()->create();
        $doctor    = User::factory()->doctor()->forClinic($clinic)->create();
        $patient   = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $bookedBy  = User::factory()->assistant()->forClinic($clinic)->create();

        // Use a unique hour offset per factory call to avoid the
        // doctor/date/start_time unique-index violation when creating batches.
        static $hour = 8;
        $startHour = ($hour++ % 9) + 8; // cycles 08 – 16
        $start = sprintf('%02d:00', $startHour);
        $end   = sprintf('%02d:00', $startHour + 1);

        return [
            'clinic_id'        => $clinic->id,
            'doctor_id'        => $doctor->id,
            'patient_id'       => $patient->id,
            'booked_by'        => $bookedBy->id,
            'appointment_date' => now()->next('Monday')->format('Y-m-d'),
            'start_time'       => $start,
            'end_time'         => $end,
            'status'           => AppointmentStatus::PENDING,
            'notes'            => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => AppointmentStatus::CONFIRMED]);
    }

    public function completed(): static
    {
        return $this->state(['status' => AppointmentStatus::COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => AppointmentStatus::CANCELLED]);
    }
}
