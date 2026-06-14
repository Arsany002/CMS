<?php

namespace Tests\Feature\SuperAdmin;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use Tests\ApiTestCase;

class UserControllerTest extends ApiTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAsPassport($this->admin);
    }

    // ─── GET /api/v1/super-admin/users ──────────────────────────────────────

    public function test_super_admin_can_list_all_users(): void
    {
        User::factory()->doctor()->count(3)->create();

        // admin + 3 doctors = 4 total
        $response = $this->getJson('/api/v1/super-admin/users');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(4, 'data');
    }

    public function test_user_list_returns_correct_json_structure(): void
    {
        $response = $this->getJson('/api/v1/super-admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'role', 'is_active'],
                ],
            ]);
    }

    // ─── POST /api/v1/super-admin/users ─────────────────────────────────────

    public function test_super_admin_can_create_a_doctor(): void
    {
        $clinic = Clinic::factory()->create();

        $response = $this->postJson('/api/v1/super-admin/users', [
            'clinic_id'             => $clinic->id,
            'name'                  => 'Dr. Test',
            'email'                 => 'drtest@clinic.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone'                 => '0123456789',
            'role'                  => 'doctor',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'User created successfully'])
            ->assertJsonPath('data.role', 'doctor');

        $this->assertDatabaseHas('users', [
            'email' => 'drtest@clinic.test',
            'role'  => 'doctor',
        ]);
    }

    public function test_super_admin_cannot_create_duplicate_email(): void
    {
        $clinic          = Clinic::factory()->create();
        $existingEmail   = 'taken@clinic.test';

        // Arrange: a user with the target email already exists
        User::factory()->doctor()->forClinic($clinic)->create(['email' => $existingEmail]);

        // Act: attempt to create a second user with the same email
        $response = $this->postJson('/api/v1/super-admin/users', [
            'clinic_id'             => $clinic->id,
            'name'                  => 'Duplicate User',
            'email'                 => $existingEmail,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone'                 => '0100000099',
            'role'                  => 'assistant',
        ]);

        // Assert: unique:users,email rule fires → 422 with an error on the email field
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_created_user_is_active_by_default(): void
    {
        $clinic = Clinic::factory()->create();

        $this->postJson('/api/v1/super-admin/users', [
            'clinic_id'             => $clinic->id,
            'name'                  => 'Assistant',
            'email'                 => 'asst@clinic.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'assistant',
        ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'asst@clinic.test',
            'is_active' => 1,
        ]);
    }

    // ─── GET /api/v1/super-admin/users/{id} ─────────────────────────────────

    public function test_super_admin_can_view_a_single_user(): void
    {
        $user = User::factory()->doctor()->create(['name' => 'View Me']);

        $response = $this->getJson("/api/v1/super-admin/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'View Me');
    }

    // ─── PUT /api/v1/super-admin/users/{id} ─────────────────────────────────

    public function test_super_admin_can_update_a_user(): void
    {
        $clinic = Clinic::factory()->create();
        $user   = User::factory()->doctor()->forClinic($clinic)->create();

        $response = $this->putJson("/api/v1/super-admin/users/{$user->id}", [
            'clinic_id' => $clinic->id,
            'name'      => 'Updated Name',
            'email'     => $user->email,
            'role'      => 'doctor',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'User updated successfully'])
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    // ─── PATCH /api/v1/super-admin/users/{id}/toggle ────────────────────────

    public function test_super_admin_can_deactivate_a_user(): void
    {
        $user = User::factory()->doctor()->create(['is_active' => true]);

        $response = $this->patchJson("/api/v1/super-admin/users/{$user->id}/toggle");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => 0]);
    }

    public function test_super_admin_can_reactivate_a_user(): void
    {
        $user = User::factory()->doctor()->inactive()->create();

        $response = $this->patchJson("/api/v1/super-admin/users/{$user->id}/toggle");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', true);
    }

    // ─── PATCH /api/v1/super-admin/users/{id}/role ──────────────────────────

    public function test_super_admin_can_change_another_users_role(): void
    {
        $user = User::factory()->doctor()->create();

        $response = $this->patchJson("/api/v1/super-admin/users/{$user->id}/role", [
            'role' => 'assistant',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'role' => 'assistant',
        ]);
    }

    public function test_super_admin_cannot_demote_their_own_role(): void
    {
        $response = $this->patchJson("/api/v1/super-admin/users/{$this->admin->id}/role", [
            'role' => 'doctor',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('users', [
            'id'   => $this->admin->id,
            'role' => 'super_admin',
        ]);
    }
}
