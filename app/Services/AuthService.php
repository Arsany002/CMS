<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\AccountDeactivatedException;
use App\Models\User;
use App\Repositories\ClinicRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider as SocialiteProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthService
{
    public function __construct(
        private UserRepository $userRepo,
        private ClinicRepository $clinicRepo,
    ) {}

    public function login(string $email, string $password): array
    {
        $user = $this->userRepo->getUserByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw new AccountDeactivatedException();
        }

        return [
            'user'  => $user,
            'token' => $this->userRepo->createApiToken($user),
        ];
    }

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $data['password'] = Hash::make($data['password']);
            $data['is_active'] = $data['is_active'] ?? true;

            $user = $this->userRepo->createUser($data);

            return [
                'user'  => $user,
                'token' => $this->userRepo->createApiToken($user),
            ];
        });
    }

    public function logout(User $user): void
    {
        $this->userRepo->revokeCurrentToken($user);
    }

    public function me(string $userId): User
    {
        return $this->userRepo->getUserWithClinic($userId);
    }

    // ─── Google OAuth ────────────────────────────────────────────────────────────

    /**
     * Validate the role/clinic parameters, store OAuth context in cache, and
     * return a redirect to Google's OAuth consent screen.
     *
     * @throws ValidationException
     */
    public function buildGoogleRedirect(?string $role, ?string $clinicId): RedirectResponse
    {
        if ($role !== null) {
            $this->validateGoogleRegistrationParams($role, $clinicId);
        }

        $stateKey = Str::uuid()->toString();

        Cache::put(
            "google_oauth:{$stateKey}",
            ['role' => $role, 'clinic_id' => $clinicId],
            now()->addMinutes(10)
        );

        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver('google');

        return $driver
            ->stateless()
            ->with(['state' => $stateKey])
            ->redirect();
    }

    /**
     * Handle the Google callback: verify state, resolve the Google user, and
     * find/create/link the local user. Returns a one-time exchange code.
     *
     * @throws \RuntimeException
     * @throws AccountDeactivatedException
     * @throws ValidationException
     */
    public function handleGoogleCallback(string $stateKey): string
    {
        $context = Cache::pull("google_oauth:{$stateKey}");

        if ($context === null) {
            throw new \RuntimeException('OAuth state is invalid or has expired.');
        }

        /** @var SocialiteProvider $driver */
        $driver = Socialite::driver('google');
        $googleUser = $driver->stateless()->user();

        $user = DB::transaction(function () use ($googleUser, $context) {
            // 1. Match by google_id first (most reliable)
            $user = $this->userRepo->findByGoogleId($googleUser->getId());

            // 2. Fall back to matching by email (links existing email accounts)
            if (! $user) {
                $user = $this->userRepo->getUserByEmail($googleUser->getEmail());
            }

            if ($user) {
                // Link Google account if this is the first time signing in with Google
                if (! $user->google_id) {
                    $this->userRepo->linkGoogleAccount(
                        $user,
                        $googleUser->getId(),
                        $googleUser->getAvatar()
                    );
                    $user = $user->fresh();
                }
            } else {
                // New user — role + clinic are required for registration
                if (empty($context['role'])) {
                    throw new \RuntimeException('registration_required');
                }

                $user = $this->userRepo->createUser([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'password'  => null,
                    'role'      => $context['role'],
                    'clinic_id' => $context['clinic_id'] ?: null,
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                    'is_active' => true,
                ]);
            }

            return $user;
        });

        if (! $user->is_active) {
            throw new AccountDeactivatedException();
        }

        // Issue a short-lived one-time exchange code (5 minutes)
        $exchangeCode = Str::uuid()->toString();
        Cache::put("google_exchange:{$exchangeCode}", $user->id, now()->addMinutes(5));

        return $exchangeCode;
    }

    /**
     * Exchange a one-time code (created in handleGoogleCallback) for a
     * long-lived Passport token and user object.
     *
     * @throws \RuntimeException
     */
    public function exchangeGoogleCode(string $code): array
    {
        $userId = Cache::pull("google_exchange:{$code}");

        if ($userId === null) {
            throw new \RuntimeException('The exchange code is invalid or has expired.');
        }

        $user = $this->userRepo->getUserWithClinic($userId);

        return [
            'user'  => $user,
            'token' => $this->userRepo->createApiToken($user),
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────────────────────

    private function validateGoogleRegistrationParams(string $role, ?string $clinicId): void
    {
        if (! in_array($role, ['doctor', 'assistant'], true)) {
            throw ValidationException::withMessages([
                'role' => ['Public Google registration only allows the doctor or assistant role.'],
            ]);
        }

        if (empty($clinicId)) {
            throw ValidationException::withMessages([
                'clinic_id' => ['A clinic must be selected for doctor and assistant registration.'],
            ]);
        }

        $clinic = $this->clinicRepo->getClinicById($clinicId);

        if (! $clinic->is_active) {
            throw ValidationException::withMessages([
                'clinic_id' => ['The selected clinic is not active.'],
            ]);
        }
    }
}
