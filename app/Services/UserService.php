<?php

namespace App\Services;

use App\Exceptions\SelfDemotionException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(private UserRepository $repo) {}

    public function paginate(?string $clinicId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repo->getAllUsers($clinicId, $perPage);
    }

    public function findById(string $id, ?string $clinicId = null): User
    {
        return $this->repo->getUserById($id, $clinicId);
    }

    public function getDoctorsForClinic(string $clinicId): Collection
    {
        return $this->repo->getDoctorsForClinic($clinicId);
    }

    /**
     * Create a new user, hashing the password and setting the active default.
     */
    public function create(array $data): User
    {
        $data['password']  = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        return $this->repo->createUser($data);
    }

    /**
     * Update a user's details, re-hashing the password only when a new one is supplied.
     */
    public function update(string $id, array $data, ?string $clinicId = null): User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->repo->updateUser($id, $data, $clinicId);
    }

    public function toggleStatus(string $id, ?string $clinicId = null): User
    {
        return $this->repo->toggleUserStatus($id, $clinicId);
    }

    /**
     * Change a user's role, preventing a super_admin from demoting themselves.
     *
     * @throws SelfDemotionException
     */
    public function updateRole(string $currentUserId, string $targetUserId, string $role): User
    {
        if ($currentUserId === $targetUserId && $role !== 'super_admin') {
            throw new SelfDemotionException();
        }

        return $this->repo->updateUserRole($targetUserId, $role);
    }
}
