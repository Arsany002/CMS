<?php

namespace App\Services;

use App\Exceptions\ClinicScopeViolationException;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PatientService
{
    public function __construct(private PatientRepository $repo) {}

    public function allForClinic(string $clinicId, ?string $search = null): LengthAwarePaginator
    {
        return $this->repo->allForClinic($clinicId, $search);
    }

    public function createForClinic(array $data, string $clinicId): Patient
    {
        $data['clinic_id'] = $clinicId;

        return $this->repo->createPatient($data);
    }

    public function findForClinic(string $id, string $clinicId): Patient
    {
        $patient = $this->repo->getPatientById($id);

        if ($patient->clinic_id !== $clinicId) {
            throw new ClinicScopeViolationException();
        }

        return $patient;
    }

    public function updateForClinic(string $id, array $data, string $clinicId): Patient
    {
        $this->findForClinic($id, $clinicId);

        return $this->repo->updatePatient($id, $data);
    }
}
