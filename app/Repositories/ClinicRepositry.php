<?php
namespace App\Repositories;
use App\Models\Clinic;
use App\Models\User;

class ClinicRepositry
{
    public function getAllClinics($clinicId = null)
    {
        $query = Clinic::query();
        if ($clinicId !== null) {
            $query->where('id', $clinicId);
        }
        return $query->get();
    }

    public function getClinicById($id, $clinicId = null)
    {
        $query = Clinic::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
         return $query->where('id', $id)->firstOrFail($id);
    }

   

    public function createClinic(array $data, $clinicId = null)
    {
        return Clinic::create($data);
    }

    public function updateClinic($id, array $data, $clinicId = null)
    {
        $query = Clinic::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        $clinic = $query->where('id', $id)->firstOrFail($id);
        $clinic->update($data);
        return $clinic;
    }

    public function deleteClinic($id, $clinicId = null)
    {
        $query = Clinic::query();
        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId)
                    ->where('id', $id);
        }
        $clinic = $query->where('id', $id)->firstOrFail($id);
        $clinic->delete();
        return true;
    }
    public function toggleClinicStatus($id)
    {
        $clinic = Clinic::findOrFail($id);
        $clinic->is_active = !$clinic->is_active;
        $clinic->save();
        return $clinic;
    }
}