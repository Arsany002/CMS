<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'clinic_id' => null,
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => static::$password ??= Hash::make('password'),
            'phone'     => fake()->phoneNumber(),
            'role'      => UserRole::DOCTOR,
            'is_active' => true,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state([
            'role'      => UserRole::SUPER_ADMIN,
            'clinic_id' => null,
        ]);
    }

    public function doctor(): static
    {
        return $this->state(['role' => UserRole::DOCTOR]);
    }

    public function assistant(): static
    {
        return $this->state(['role' => UserRole::ASSISTANT]);
    }

    public function forClinic(Clinic $clinic): static
    {
        return $this->state(['clinic_id' => $clinic->id]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
