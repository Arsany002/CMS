<?php

namespace Tests\Feature;

use App\Models\Clinic;
use Database\Seeders\LocalClinicSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LocalClinicSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_local_clinic_seeder_creates_an_active_demo_clinic(): void
    {
        Clinic::query()->update(['is_active' => false]);

        $this->seed(LocalClinicSeeder::class);

        $this->assertDatabaseHas('clinics', [
            'name' => LocalClinicSeeder::NAME,
            'is_active' => 1,
        ]);
    }

    public function test_local_clinic_seeder_is_idempotent(): void
    {
        Clinic::query()->update(['is_active' => false]);
        $initialDemoClinicCount = Clinic::where('name', LocalClinicSeeder::NAME)->count();

        $this->seed(LocalClinicSeeder::class);
        $this->seed(LocalClinicSeeder::class);

        $this->assertSame(
            max(1, $initialDemoClinicCount),
            Clinic::where('name', LocalClinicSeeder::NAME)->count()
        );
    }

    public function test_local_clinic_seeder_does_not_create_demo_clinic_when_active_clinic_exists(): void
    {
        $initialDemoClinicCount = Clinic::where('name', LocalClinicSeeder::NAME)->count();
        Clinic::factory()->create(['name' => 'Existing Active Clinic']);

        $this->seed(LocalClinicSeeder::class);

        $this->assertDatabaseHas('clinics', [
            'name' => 'Existing Active Clinic',
            'is_active' => 1,
        ]);
        $this->assertSame(
            $initialDemoClinicCount,
            Clinic::where('name', LocalClinicSeeder::NAME)->count()
        );
    }

    public function test_local_clinic_seeder_reactivates_demo_clinic_without_overwriting_details(): void
    {
        Clinic::query()->update(['is_active' => false]);
        $clinic = Clinic::factory()->inactive()->create([
            'name' => LocalClinicSeeder::NAME,
            'address' => 'Custom Local Address',
            'phone' => '01111111111',
            'email' => 'custom.demo@clinic.test',
        ]);

        $this->seed(LocalClinicSeeder::class);

        $clinic->refresh();

        $this->assertTrue($clinic->is_active);
        $this->assertSame('Custom Local Address', $clinic->address);
        $this->assertSame('01111111111', $clinic->phone);
        $this->assertSame('custom.demo@clinic.test', $clinic->email);
    }
}
