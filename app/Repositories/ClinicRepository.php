<?php

namespace App\Repositories;

use App\Models\Clinic;
use Illuminate\Pagination\LengthAwarePaginator;

class ClinicRepository
{
    public function getAllClinics(int $perPage = 15): LengthAwarePaginator
    {
        return Clinic::orderBy('name')->paginate($perPage);
    }

    public function getClinicById(int|string $id): Clinic
    {
        return Clinic::findOrFail($id);
    }

    public function createClinic(array $data): Clinic
    {
        return Clinic::create($data);
    }

    public function updateClinic(Clinic $clinic, array $data): Clinic
    {
        $clinic->update($data);
        return $clinic;
    }

    public function deleteClinic(Clinic $clinic): bool
    {
        $clinic->delete();
        return true;
    }

    public function saveClinic(Clinic $clinic): Clinic
    {
        $clinic->save();
        return $clinic;
    }

    public function toggleActive(Clinic $clinic): Clinic
    {
        $clinic->is_active = !$clinic->is_active;
        $clinic->save();
        return $clinic;
    }

    public function getActiveClinics(): \Illuminate\Database\Eloquent\Collection
    {
        return Clinic::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function count(): int
    {
        return Clinic::count();
    }
}
