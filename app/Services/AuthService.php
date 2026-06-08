<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Validate credentials and issue a Passport Personal Access Token.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // PASSPORT DIFFERENCE 1: Use accessToken instead of plainTextToken
        $token = $user->createToken('Clinic API Token')->accessToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Revoke the user's current Passport token.
     */
    public function logout(User $user): void
    {
        // PASSPORT DIFFERENCE 2: You access the token instance and call revoke()
        /** @var \Laravel\Passport\Token|null $token */
        $token = $user->token();

        $token?->revoke();
    }
}
