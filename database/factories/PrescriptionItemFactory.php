<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'medicine_name'   => fake()->word() . ' ' . fake()->numberBetween(5, 500) . 'mg',
            'dosage'          => fake()->randomElement(['1 tablet', '2 tablets', '5ml', '10ml']),
            'frequency'       => fake()->randomElement(['Once daily', 'Twice daily', 'Three times daily']),
            'duration'        => fake()->randomElement(['3 days', '5 days', '7 days', '14 days']),
            'notes'           => fake()->optional()->sentence(),
        ];
    }
}
