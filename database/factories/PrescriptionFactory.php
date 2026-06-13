<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'doctor_id'      => User::factory()->doctor(),
            'patient_id'     => Patient::factory(),
            'clinic_id'      => Clinic::factory(),
            'diagnosis'      => fake()->sentence(),
            'notes'          => fake()->optional()->sentence(),
        ];
    }
}
