<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Clinic;
use App\Models\User;
use Tests\ApiTestCase;

class ClinicControllerTest extends ApiTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAsPassport($this->admin);
    }

    // ─── GET /api/v1/super-admin/clinics ────────────────────────────────────

    public function test_super_admin_can_list_all_clinics(): void
    {
        Clinic::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/super-admin/clinics');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_clinic_list_returns_correct_json_structure(): void
    {
        Clinic::factory()->create(['name' => 'Alpha Clinic']);

        $response = $this->getJson('/api/v1/super-admin/clinics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'address', 'phone', 'email', 'is_active'],
                ],
            ]);
    }

    // ─── POST /api/v1/super-admin/clinics ───────────────────────────────────

    public function test_super_admin_can_create_a_clinic(): void
    {
        $payload = [
            'name'    => 'New Clinic',
            'address' => '123 Health St',
            'phone'   => '0123456789',
            'email'   => 'newclinic@health.test',
        ];

        $response = $this->postJson('/api/v1/super-admin/clinics', $payload);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Clinic created'])
            ->assertJsonPath('data.name', 'New Clinic')
            ->assertJsonPath('data.email', 'newclinic@health.test');

        $this->assertDatabaseHas('clinics', ['email' => 'newclinic@health.test']);
    }

    public function test_new_clinic_is_active_by_default(): void
    {
        $this->postJson('/api/v1/super-admin/clinics', [
            'name'    => 'Default Active Clinic',
            'address' => '1 Health Ave',
            'phone'   => '0100000000',
            'email'   => 'active@clinic.test',
        ]);

        $this->assertDatabaseHas('clinics', [
            'email'     => 'active@clinic.test',
            'is_active' => 1,
        ]);
    }

    // ─── GET /api/v1/super-admin/clinics/{id} ───────────────────────────────

    public function test_super_admin_can_view_a_single_clinic(): void
    {
        $clinic = Clinic::factory()->create(['name' => 'Detail Clinic']);

        $response = $this->getJson("/api/v1/super-admin/clinics/{$clinic->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $clinic->id)
            ->assertJsonPath('data.name', 'Detail Clinic');
    }

    // ─── PUT /api/v1/super-admin/clinics/{id} ───────────────────────────────

    public function test_super_admin_can_update_a_clinic(): void
    {
        $clinic = Clinic::factory()->create();

        $response = $this->putJson("/api/v1/super-admin/clinics/{$clinic->id}", [
            'name'    => 'Updated Name',
            'address' => $clinic->address,
            'phone'   => $clinic->phone,
            'email'   => $clinic->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Clinic updated'])
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('clinics', [
            'id'   => $clinic->id,
            'name' => 'Updated Name',
        ]);
    }

    // ─── PATCH /api/v1/super-admin/clinics/{id}/toggle ──────────────────────

    public function test_super_admin_can_deactivate_an_active_clinic(): void
    {
        $clinic = Clinic::factory()->create(['is_active' => true]);

        $response = $this->patchJson("/api/v1/super-admin/clinics/{$clinic->id}/toggle");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Clinic status toggled'])
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('clinics', ['id' => $clinic->id, 'is_active' => 0]);
    }

    public function test_super_admin_can_reactivate_an_inactive_clinic(): void
    {
        $clinic = Clinic::factory()->inactive()->create();

        $response = $this->patchJson("/api/v1/super-admin/clinics/{$clinic->id}/toggle");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', true);
    }
}
