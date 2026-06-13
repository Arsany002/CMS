<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\ClinicScopeViolationException;
use App\Exceptions\InvalidAppointmentStateException;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Repositories\PrescriptionRepository;

class PrescriptionService
{
    public function __construct(
        private PrescriptionRepository $repo
    ) {}

    public function create(array $data, array $items, Appointment $appointment, string $doctorId)
    {
        // BR-04: Only the appointment's doctor can create a prescription
        if ($appointment->doctor_id !== $doctorId) {
            throw new ClinicScopeViolationException('You are not the doctor assigned to this appointment.');
        }

        // BR-06: No prescription can be written for a cancelled appointment
        if ($appointment->status === AppointmentStatus::CANCELLED) {
            throw new InvalidAppointmentStateException('Cannot create a prescription for a cancelled appointment.');
        }

        // Auto-fill contextual data to ensure database integrity
        $data['doctor_id']  = $doctorId;
        $data['patient_id'] = $appointment->patient_id;
        $data['clinic_id']  = $appointment->clinic_id;

        return $this->repo->create($data, $items);
    }

    public function update(Prescription $prescription, array $data, array $items): Prescription
    {
        return $this->repo->update($prescription, $data, $items);
    }
}
