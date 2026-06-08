<?php

namespace App\Repositories;
use App\Models\RefreshToken;
use Illuminate\Support\Carbon;

class RefreshTokenRepository
{
    public function create(int $userId, string $hashedToken, Carbon $expiresAt): RefreshToken
    {
        return RefreshToken::create([
            'user_id'    => $userId,
            'token'      => $hashedToken,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findById(int $id): ?RefreshToken
    {
        return RefreshToken::find($id);
    }

    public function deleteByUserId(int $userId): void
    {
        RefreshToken::where('user_id', $userId)->delete();
    }

    public function deleteById(int $id): void
    {
        RefreshToken::destroy($id);
    }

    public function deleteExpired(): void
    {
        RefreshToken::where('expires_at', '<', now())->delete();
    }
}
