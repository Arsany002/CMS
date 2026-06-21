<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class LocalClinicSeeder extends Seeder
{
    public const NAME = 'Demo Clinic';
    public const ADDRESS = '123 Demo Clinic Street';
    public const PHONE = '01000000000';
    public const EMAIL = 'demo.clinic@cms.local';

    public function run(): void
    {
        if (Clinic::where('is_active', true)->exists()) {
            $this->command?->info('Active clinic already exists; local demo clinic not changed.');

            return;
        }

        $clinic = Clinic::firstOrNew(['name' => self::NAME]);

        if (! $clinic->exists) {
            $clinic->fill([
                'address' => self::ADDRESS,
                'phone' => self::PHONE,
                'email' => self::EMAIL,
            ]);
        }

        $clinic->is_active = true;
        $clinic->save();

        $this->command?->info("Local demo clinic ensured: {$clinic->name}");
    }
}
