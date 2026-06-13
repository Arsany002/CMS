<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'clinic_id'     => Clinic::factory(),
            'name'          => fake()->name(),
            'email'         => fake()->unique()->safeEmail(),
            'phone'         => fake()->numerify('01#########'),
            'date_of_birth' => fake()->date('Y-m-d', '-18 years'),
            'gender'        => fake()->randomElement([Gender::MALE, Gender::FEMALE]),
            'address'       => fake()->address(),
        ];
    }
}
