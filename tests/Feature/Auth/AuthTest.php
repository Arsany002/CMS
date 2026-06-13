<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use Tests\ApiTestCase;

class AuthTest extends ApiTestCase
{
    // ─── POST /api/v1/auth/login ─────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->doctor()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Login successful'])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user'  => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ]);
    }

    public function test_login_response_contains_a_non_empty_bearer_token(): void
    {
        $user = User::factory()->doctor()->create(['password' => bcrypt('secret99')]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'secret99',
        ])->json('data.token');

        $this->assertNotEmpty($token);
    }

    // ─── POST /api/v1/auth/register ──────────────────────────────────────────

    public function test_guest_can_register_a_new_account(): void
    {
        $clinic = Clinic::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'New Doctor',
            'email'                 => 'newdoc@clinic.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'doctor',
            'clinic_id'             => $clinic->id,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'User registered successfully'])
            ->assertJsonStructure([
                'data' => [
                    'user'  => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'newdoc@clinic.test']);
    }

    public function test_register_stores_the_correct_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Admin User',
            'email'                 => 'admin@clinic.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'super_admin',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@clinic.test',
            'role'  => 'super_admin',
        ]);
    }

    // ─── POST /api/v1/auth/logout ────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->doctor()->create();
        $this->actingAsPassport($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Logged out successfully']);
    }

    // ─── GET /api/v1/auth/me ─────────────────────────────────────────────────

    public function test_authenticated_user_can_fetch_own_profile(): void
    {
        $clinic = Clinic::factory()->create();
        $user   = User::factory()->doctor()->forClinic($clinic)->create();
        $this->actingAsPassport($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', UserRole::DOCTOR->value);
    }

    public function test_me_response_includes_clinic_id(): void
    {
        $clinic = Clinic::factory()->create();
        $user   = User::factory()->doctor()->forClinic($clinic)->create();
        $this->actingAsPassport($user);

        $clinicId = $this->getJson('/api/v1/auth/me')->json('data.clinic_id');

        $this->assertEquals($clinic->id, $clinicId);
    }
}
