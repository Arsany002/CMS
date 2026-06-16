<?php

namespace Tests\Feature\Auth;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\ApiTestCase;

/**
 * Tests for the Google OAuth flow:
 *   GET  /api/v1/auth/google/redirect
 *   GET  /api/v1/auth/google/callback
 *   POST /api/v1/auth/google/exchange
 */
class GoogleAuthTest extends ApiTestCase
{
    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Build a fake SocialiteUser and wire up Socialite::driver('google') so that
     * stateless()->user() returns it and redirect() returns a valid response.
     */
    private function mockSocialiteDriver(
        string $googleId = 'google-123',
        string $email = 'google@example.com',
        string $name = 'Google User',
        string $avatar = 'https://example.com/avatar.jpg',
    ): void {
        $socialiteUser = (new SocialiteUser())->map([
            'id'       => $googleId,
            'email'    => $email,
            'name'     => $name,
            'avatar'   => $avatar,
            'nickname' => null,
        ]);

        $driver = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('with')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($socialiteUser);
        $driver->shouldReceive('redirect')->andReturn(
            redirect('https://accounts.google.com/o/oauth2/auth?client_id=test')
        );
        $driver->shouldReceive('scopes')->andReturnSelf();

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    private function seedValidState(string $stateKey, ?string $role = 'doctor', ?string $clinicId = null): void
    {
        Cache::put("google_oauth:{$stateKey}", ['role' => $role, 'clinic_id' => $clinicId], now()->addMinutes(10));
    }

    // ─── GET /auth/google/redirect ────────────────────────────────────────────

    public function test_redirect_returns_302_to_google_for_doctor_with_valid_clinic(): void
    {
        $clinic = Clinic::factory()->create(['is_active' => true]);
        $this->mockSocialiteDriver();

        $response = $this->get("/api/v1/auth/google/redirect?role=doctor&clinic_id={$clinic->id}");

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location', ''));
    }

    public function test_redirect_returns_302_to_google_for_assistant_with_valid_clinic(): void
    {
        $clinic = Clinic::factory()->create(['is_active' => true]);
        $this->mockSocialiteDriver();

        $response = $this->get("/api/v1/auth/google/redirect?role=assistant&clinic_id={$clinic->id}");

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location', ''));
    }

    public function test_redirect_rejects_super_admin_role_with_422(): void
    {
        $response = $this->getJson('/api/v1/auth/google/redirect?role=super_admin&clinic_id=any');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_redirect_rejects_invalid_role_with_422(): void
    {
        $response = $this->getJson('/api/v1/auth/google/redirect?role=hacker&clinic_id=any');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_redirect_rejects_missing_clinic_id_when_role_is_doctor(): void
    {
        $response = $this->getJson('/api/v1/auth/google/redirect?role=doctor');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_redirect_rejects_inactive_clinic(): void
    {
        $clinic = Clinic::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/auth/google/redirect?role=doctor&clinic_id={$clinic->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_redirect_without_role_allows_login_intent(): void
    {
        $this->mockSocialiteDriver();

        $response = $this->get('/api/v1/auth/google/redirect');

        $response->assertRedirect();
    }

    // ─── GET /auth/google/callback ────────────────────────────────────────────

    public function test_callback_creates_new_doctor_and_redirects_with_exchange_code(): void
    {
        $clinic = Clinic::factory()->create(['is_active' => true]);
        $stateKey = 'test-state-new-user';
        $this->seedValidState($stateKey, 'doctor', $clinic->id);
        $this->mockSocialiteDriver(googleId: 'g-new-001', email: 'newdoctor@google.com');

        $response = $this->get("/api/v1/auth/google/callback?state={$stateKey}");

        $response->assertRedirect();
        $location = $response->headers->get('Location', '');
        $this->assertStringContainsString('/auth/google/callback', $location);
        $this->assertStringContainsString('code=', $location);

        $this->assertDatabaseHas('users', [
            'email'     => 'newdoctor@google.com',
            'google_id' => 'g-new-001',
            'role'      => 'doctor',
            'clinic_id' => $clinic->id,
        ]);
    }

    public function test_callback_creates_new_assistant_user(): void
    {
        $clinic = Clinic::factory()->create(['is_active' => true]);
        $stateKey = 'test-state-new-asst';
        $this->seedValidState($stateKey, 'assistant', $clinic->id);
        $this->mockSocialiteDriver(googleId: 'g-asst-001', email: 'newassistant@google.com');

        $response = $this->get("/api/v1/auth/google/callback?state={$stateKey}");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newassistant@google.com', 'role' => 'assistant']);
    }

    public function test_callback_links_existing_email_account_to_google(): void
    {
        User::factory()->doctor()->create(['email' => 'existing@example.com', 'google_id' => null]);

        $stateKey = 'test-state-link';
        $this->seedValidState($stateKey, null, null);
        $this->mockSocialiteDriver(googleId: 'g-link-001', email: 'existing@example.com');

        $response = $this->get("/api/v1/auth/google/callback?state={$stateKey}");

        $response->assertRedirect();
        $this->assertStringContainsString('code=', $response->headers->get('Location', ''));
        $this->assertDatabaseHas('users', ['email' => 'existing@example.com', 'google_id' => 'g-link-001']);
    }

    public function test_callback_matches_existing_user_by_google_id_priority(): void
    {
        $clinic = Clinic::factory()->create();
        User::factory()->doctor()->forClinic($clinic)->create([
            'email'     => 'byid@example.com',
            'google_id' => 'g-existing-id',
        ]);

        $stateKey = 'test-state-by-id';
        $this->seedValidState($stateKey, null, null);
        // Different email but same google_id → should match by google_id
        $this->mockSocialiteDriver(googleId: 'g-existing-id', email: 'different@example.com');

        $response = $this->get("/api/v1/auth/google/callback?state={$stateKey}");

        $response->assertRedirect();
        $this->assertStringContainsString('code=', $response->headers->get('Location', ''));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_callback_does_not_create_duplicate_user_for_existing_email(): void
    {
        $clinic = Clinic::factory()->create(['is_active' => true]);
        User::factory()->doctor()->forClinic($clinic)->create(['email' => 'dup@example.com', 'google_id' => null]);

        $stateKey = 'test-state-dup';
        $this->seedValidState($stateKey, 'doctor', $clinic->id);
        $this->mockSocialiteDriver(googleId: 'g-new-dup', email: 'dup@example.com');

        $response = $this->get("/api/v1/auth/google/callback?state={$stateKey}");

        $response->assertRedirect();
        $this->assertStringContainsString('code=', $response->headers->get('Location', ''));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_callback_redirects_with_registration_required_for_unknown_login_user(): void
    {
        // Login intent (no role) but user doesn't exist yet
        $stateKey = 'test-state-no-role';
        $this->seedValidState($stateKey, null, null);
        $this->mockSocialiteDriver(googleId: 'g-unknown', email: 'unknown@google.com');

        $response = $this->get("/api/v1/auth/google/callback?state={$stateKey}");

        $response->assertRedirect();
        $this->assertStringContainsString('error=registration_required', $response->headers->get('Location', ''));
    }

    public function test_callback_redirects_with_error_for_deactivated_account(): void
    {
        User::factory()->doctor()->inactive()->create([
            'email'     => 'inactive@example.com',
            'google_id' => 'g-inactive-001',
        ]);

        $stateKey = 'test-state-inactive';
        $this->seedValidState($stateKey, null, null);
        $this->mockSocialiteDriver(googleId: 'g-inactive-001', email: 'inactive@example.com');

        $response = $this->get("/api/v1/auth/google/callback?state={$stateKey}");

        $response->assertRedirect();
        $this->assertStringContainsString('error=account_deactivated', $response->headers->get('Location', ''));
    }

    public function test_callback_redirects_with_state_invalid_for_bad_state(): void
    {
        // Deliberately do NOT seed any cache key
        $response = $this->get('/api/v1/auth/google/callback?state=non-existent-key');

        $response->assertRedirect();
        $this->assertStringContainsString('error=state_invalid', $response->headers->get('Location', ''));
    }

    // ─── POST /auth/google/exchange ───────────────────────────────────────────

    public function test_exchange_returns_token_and_user_for_valid_code(): void
    {
        $user = User::factory()->doctor()->create();
        $code = 'a0000000-0000-0000-0000-000000000001';
        Cache::put("google_exchange:{$code}", $user->id, now()->addMinutes(5));

        $response = $this->postJson('/api/v1/auth/google/exchange', ['code' => $code]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Google sign-in successful'])
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        // Code must be consumed (single-use)
        $this->assertNull(Cache::get("google_exchange:{$code}"));
    }

    public function test_exchange_rejects_non_uuid_format_with_422(): void
    {
        $response = $this->postJson('/api/v1/auth/google/exchange', ['code' => 'not-a-uuid']);

        $response->assertStatus(422);
    }

    public function test_exchange_rejects_expired_or_unknown_code(): void
    {
        $response = $this->postJson('/api/v1/auth/google/exchange', [
            'code' => 'b1111111-1111-1111-1111-111111111111',
        ]);

        $response->assertStatus(500);
    }

    public function test_exchange_code_is_single_use(): void
    {
        $user = User::factory()->doctor()->create();
        $code = 'c2222222-2222-2222-2222-222222222222';
        Cache::put("google_exchange:{$code}", $user->id, now()->addMinutes(5));

        $this->postJson('/api/v1/auth/google/exchange', ['code' => $code])->assertOk();
        $this->postJson('/api/v1/auth/google/exchange', ['code' => $code])->assertStatus(500);
    }

    // ─── Normal auth still works ──────────────────────────────────────────────

    public function test_normal_login_still_works(): void
    {
        $user = User::factory()->doctor()->create(['password' => bcrypt('secret88')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'secret88',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true, 'message' => 'Login successful']);
    }

    public function test_normal_register_still_works(): void
    {
        $clinic = Clinic::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Normal User',
            'email'                 => 'normal@clinic.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'doctor',
            'clinic_id'             => $clinic->id,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
    }
}
