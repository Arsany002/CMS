<?php

namespace Tests\Feature;

use App\Models\Clinic;
use Database\Seeders\LocalClinicSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicClinicsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_clinics_returns_active_demo_clinic_after_setup_seeding(): void
    {
        Clinic::query()->update(['is_active' => false]);

        $this->seed(LocalClinicSeeder::class);

        $response = $this->getJson('/api/v1/public/clinics');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', LocalClinicSeeder::NAME)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ])
            ->assertJsonMissingPath('data.0.address')
            ->assertJsonMissingPath('data.0.phone')
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.is_active');
    }

    public function test_public_clinics_returns_only_active_clinics_without_authentication(): void
    {
        $active = Clinic::factory()->create(['name' => 'Active Clinic']);
        $inactive = Clinic::factory()->inactive()->create(['name' => 'Inactive Clinic']);

        $response = $this->getJson('/api/v1/public/clinics');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $active->id,
                'name' => 'Active Clinic',
            ])
            ->assertJsonMissing([
                'id' => $inactive->id,
                'name' => 'Inactive Clinic',
            ]);
    }
}
