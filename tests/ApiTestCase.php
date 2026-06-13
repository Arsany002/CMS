<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the Passport personal access client required by createToken().
        // actingAsPassport() bypasses this for protected-route tests, but the
        // Auth endpoints (login / register) call createToken() for real.
        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Test Personal Access Client',
            null,
        );
    }

    /**
     * Authenticate the given user via a mock Passport token.
     *
     * Centralises the Passport::actingAs() call so Intelephense resolves the
     * Authenticatable type in a single place instead of every test file.
     */
    protected function actingAsPassport(User $user): void
    {
        /** @var Authenticatable $user */
        Passport::actingAs($user);
    }
}
