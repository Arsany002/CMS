<?php
namespace App\Repositories;
use App\Models\User;
class UserRepositry
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
        $query = User::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        return $query->where('id', $id)->firstOrFail($id);
    }
    public function createUser(array $data)
    {
        return User::create($data);
    }
    public function updateUser($id, array $data, $clinicId = null)
    {
        $query = User::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }
        $user = $query->where('id', $id)->firstOrFail($id);
        $user->update($data);
        return $user;
    }
    public function deleteUser($id, $clinicId = null)
    {
        $query = User::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        $user = $query->where('id', $id)->firstOrFail($id);
        $user->delete();
        return true;
    }
    public function toggleUserStatus($id, $clinicId = null)
    {
        $query = User::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        $user = $query->where('id', $id)->firstOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();
        return $user;
    }
}