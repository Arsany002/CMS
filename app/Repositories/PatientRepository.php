<?php
namespace App\Repositories;
use App\Models\Patient;
class PatientRepository
{
    public function getAllPatients($clinicId = null)
    {
        $query = Patient::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }
        return $query->get();
    }

    public function getPatientById($id, $clinicId = null)
    {
        $query = Patient::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        return $query->where('id', $id)->firstOrFail();
    }

    public function createPatient(array $data, $clinicId = null)
    {
        return Patient::create($data);
    }

    public function updatePatient($id, array $data, $clinicId = null)
    {
        $query = Patient::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        $patient = $query->where('id', $id)->firstOrFail($id);
        $patient->update($data);
        return $patient;
    }

    public function deletePatient($id, $clinicId = null)
    {
        $query = Patient::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        $patient = $query->where('id', $id)->firstOrFail($id);
        $patient->delete();
        return true;
    }
    public function allForClinic(int $clinicId, ?string $search = null)
    {
        return Patient::where('clinic_id', $clinicId)
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%"))
            ->get();
    }
}