<?php

namespace App\Services;

use App\Exceptions\AccountDeactivatedException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private UserRepository $userRepo) {}

    /**
     * Validate credentials and issue a Passport Personal Access Token.
     */
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
}
