<?php
namespace App\Repositories;
use App\Models\User;
class UserRepository
{
    public function getAllUsers($clinicId = null)
    {
        $query = User::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }
        return $query->get();
    }
    public function getUserById($id, $clinicId = null)
    {
        $query = User::query()->where('id', $id);

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
    public function updateUser($id, array $data, $clinicId = null)
    {
        $query = User::query()->where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $user = $query->firstOrFail();
        $user->update($data);
        return $user;
    }
    public function deleteUser($id, $clinicId = null)
    {
        $query = User::query()->where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $user = $query->firstOrFail();
        $user->delete();
        return true;
    }
    public function toggleUserStatus($id, $clinicId = null)
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
}