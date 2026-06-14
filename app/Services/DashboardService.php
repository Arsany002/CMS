<?php

namespace App\Services;

use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PrescriptionRepository;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardService
{
    public function __construct(
        private AppointmentRepository $appointmentRepo,
        private PrescriptionRepository $prescriptionRepo,
        private ClinicRepository $clinicRepo,
        private UserRepository $userRepo,
        private PatientRepository $patientRepo,
    ) {}

    public function getStats(): array
    {
        return [
            'total_clinics'       => $this->clinicRepo->count(),
            'total_users'         => $this->userRepo->count(),
            'total_patients'      => $this->patientRepo->count(),
            'total_appointments'  => $this->appointmentRepo->count(),
            'total_prescriptions' => $this->prescriptionRepo->count(),
            'appointments_today'  => $this->appointmentRepo->countToday(),
        ];
    }

    public function getAllAppointments(array $filters = []): LengthAwarePaginator
    {
        return $this->appointmentRepo->allForAdmin($filters);
    }

    public function getAllPrescriptions(): LengthAwarePaginator
    {
        return $this->prescriptionRepo->allForAdmin();
    }
}
