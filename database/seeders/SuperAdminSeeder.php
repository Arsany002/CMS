<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate is idempotent — safe to run multiple times.
        // The 'hashed' cast on User::$password auto-hashes the plain-text
        // value; do NOT wrap it in Hash::make() or the hash will be doubled.
        User::updateOrCreate(
            ['email' => 'arsany.ayman02@gmail.com'],
            [
                'name'      => 'Arsany Ayman',
                'password'  => 'Aroaymo02',
                'role'      => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'clinic_id' => null,
            ]
        );

        $this->command?->info('Super admin seeded: arsany.ayman02@gmail.com');
    }
}
