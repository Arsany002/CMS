<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\ClinicScopeViolationException;
use App\Exceptions\InvalidAppointmentStateException;
use App\Models\Prescription;
use App\Repositories\AppointmentRepository;
use App\Repositories\PrescriptionRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PrescriptionService
{
    public function __construct(
        private PrescriptionRepository $repo,
        private AppointmentRepository $appointmentRepo,
    ) {}

    public function allForDoctor(string $doctorId): LengthAwarePaginator
    {
        return $this->repo->allForDoctor($doctorId);
    }

    public function create(array $data, array $items, string $appointmentId, string $doctorId): Prescription
    {
        $appointment = $this->appointmentRepo->find($appointmentId);

        // BR-04: Only the appointment's doctor can create a prescription
        if ($appointment->doctor_id !== $doctorId) {
            throw new ClinicScopeViolationException('You are not the doctor assigned to this appointment.');
        }

        // BR-06: No prescription can be written for a cancelled appointment
        if ($appointment->status === AppointmentStatus::CANCELLED) {
            throw new InvalidAppointmentStateException('Cannot create a prescription for a cancelled appointment.');
        }

        $data['doctor_id']  = $doctorId;
        $data['patient_id'] = $appointment->patient_id;
        $data['clinic_id']  = $appointment->clinic_id;

        return $this->repo->create($data, $items);
    }

    public function update(Prescription $prescription, array $data, array $items): Prescription
    {
        return $this->repo->update($prescription, $data, $items);
    }

    public function findForDoctor(string $id, string $doctorId): Prescription
    {
        $prescription = $this->repo->find($id);

        if ($prescription->doctor_id !== $doctorId) {
            throw new ClinicScopeViolationException('You do not have access to this prescription.');
        }

        return $prescription;
    }

    public function updateForDoctor(string $id, string $doctorId, array $data, array $items): Prescription
    {
        $prescription = $this->findForDoctor($id, $doctorId);

        return $this->repo->update($prescription, $data, $items);
    }
}
