<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate is idempotent and preserves any local changes made to
        // an existing account. The 'hashed' cast auto-hashes the plain-text
        // password when a new account is created.
        $user = User::firstOrCreate(
            ['email' => 'arsany.ayman02@gmail.com'],
            [
                'name'      => 'Arsany Ayman',
                'password'  => 'Aroaymo02',
                'role'      => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'clinic_id' => null,
            ]
        );

        $message = $user->wasRecentlyCreated
            ? 'Super admin created: arsany.ayman02@gmail.com'
            : 'Super admin already exists: arsany.ayman02@gmail.com';

        $this->command?->info($message);
    }
}
