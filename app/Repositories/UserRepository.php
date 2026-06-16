<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function getAllUsers(?string $clinicId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::select(['id', 'clinic_id', 'name', 'email', 'phone', 'role', 'is_active', 'created_at'])
            ->with('clinic:id,name')
            ->orderBy('name');

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->paginate($perPage);
    }

    public function getUserById(string $id, ?string $clinicId = null)
    {
        $query = User::where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->firstOrFail();
    }

    public function getUserByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function createUser(array $data)
    {
        return User::create($data);
    }

    public function updateUser(string $id, array $data, ?string $clinicId = null)
    {
        $query = User::query()->where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $user = $query->firstOrFail();
        $user->update($data);
        return $user;
    }

    public function deleteUser(string $id, ?string $clinicId = null)
    {
        $query = User::query()->where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $user = $query->firstOrFail();
        $user->delete();
        return true;
    }

    public function toggleUserStatus(string $id, ?string $clinicId = null)
    {
        $query = User::query()->where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $user = $query->firstOrFail();
        $user->is_active = !$user->is_active;
        $user->save();
        return $user;
    }

    public function updateUserRole(string $id, string $role): User
    {
        $user = User::findOrFail($id);
        $user->update(['role' => $role]);
        return $user;
    }

    public function getUserWithClinic(string $id): User
    {
        return User::with('clinic')->findOrFail($id);
    }

    public function createApiToken(User $user): string
    {
        return $user->createToken('Clinic API Token')->accessToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        /** @var \Laravel\Passport\Token|null $token */
        $token = $user->token();
        $token?->revoke();
    }

    public function getDoctorsForClinic(string $clinicId): Collection
    {
        return User::where('clinic_id', $clinicId)
            ->where('role', UserRole::DOCTOR)
            ->where('is_active', true)
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->get();
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return User::where('google_id', $googleId)->first();
    }

    public function linkGoogleAccount(User $user, string $googleId, ?string $avatar): User
    {
        $user->update(['google_id' => $googleId, 'avatar' => $avatar]);
        return $user->fresh();
    }

    public function count(): int
    {
        return User::count();
    }
}
