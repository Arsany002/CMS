<?php

namespace App\Services;

use App\Models\DoctorSchedule;
use App\Repositories\ScheduleRepository;
use Illuminate\Database\Eloquent\Collection;

class ScheduleService
{
    public function __construct(private ScheduleRepository $repo) {}

    public function allForDoctor(string $doctorId): Collection
    {
        return $this->repo->allForDoctor($doctorId);
    }

    public function createForDoctor(array $data, string $doctorId, string $clinicId): DoctorSchedule
    {
        $data['doctor_id'] = $doctorId;
        $data['clinic_id'] = $clinicId;
        $data['is_active'] = true;

        return $this->repo->create($data);
    }

    public function findForDoctor(string $id, string $doctorId): DoctorSchedule
    {
        return $this->repo->findForDoctor($id, $doctorId);
    }

    public function updateForDoctor(string $id, string $doctorId, array $data): DoctorSchedule
    {
        return $this->repo->updateForDoctor($id, $doctorId, $data);
    }

    public function deleteForDoctor(string $id, string $doctorId): void
    {
        $this->repo->deleteForDoctor($id, $doctorId);
    }
}
