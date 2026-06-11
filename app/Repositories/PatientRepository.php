<?php

namespace App\Repositories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;

class PatientRepository
{
    public function getAllPatients(?string $clinicId = null): Collection
    {
        $query = Patient::select(['id', 'clinic_id', 'name', 'email', 'phone', 'date_of_birth', 'gender']);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->get();
    }

    public function getPatientById(string $id, ?string $clinicId = null): Patient
    {
        $query = Patient::where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->firstOrFail();
    }

    public function createPatient(array $data): Patient
    {
        return Patient::create($data);
    }

    public function updatePatient(string $id, array $data, ?string $clinicId = null): Patient
    {
        $query = Patient::where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $patient = $query->firstOrFail();
        $patient->update($data);
        return $patient;
    }

    public function deletePatient(string $id, ?string $clinicId = null): bool
    {
        $query = Patient::where('id', $id);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $patient = $query->firstOrFail();
        $patient->delete();
        return true;
    }

    public function allForClinic(string $clinicId, ?string $search = null): Collection
    {
        return Patient::select(['id', 'clinic_id', 'name', 'email', 'phone', 'date_of_birth', 'gender'])
            ->where('clinic_id', $clinicId)
            ->when($search, fn($q) => $q->whereFullText(['name', 'phone'], $search))
            ->get();
    }
}
