<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Both seeders are idempotent (updateOrCreate / exists-check) so this is
     * safe to run multiple times and after migrate:fresh.
     *
     * For local dev, prefer `php artisan cms:local-setup` which also handles
     * Passport key generation and permission fixing before calling these.
     */
    public function run(): void
    {
        $this->call([
            PassportClientSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
