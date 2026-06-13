<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PrescriptionResource;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Repositories\AppointmentRepository;
use App\Repositories\PrescriptionRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AppointmentRepository $appointmentRepo,
        private PrescriptionRepository $prescriptionRepo,
    ) {}

    /**
     * Return a high-level summary of system stats.
     */
    public function index(): JsonResponse
    {
        return $this->success(
            data: [
                'total_clinics'       => Clinic::count(),
                'total_users'         => User::count(),
                'total_patients'      => Patient::count(),
                'total_appointments'  => Appointment::count(),
                'total_prescriptions' => Prescription::count(),
                'appointments_today'  => Appointment::whereDate('appointment_date', today())->count(),
            ],
            message: 'Dashboard statistics'
        );
    }

    /**
     * Return a paginated list of all appointments across all clinics.
     */
    public function appointments(Request $request): JsonResponse
    {
        $filters = $request->only(['clinic_id', 'doctor_id', 'status', 'date']);

        return $this->success(
            data: AppointmentResource::collection(
                $this->appointmentRepo->allForAdmin($filters)
            )
        );
    }

    /**
     * Return a paginated list of all prescriptions across all clinics.
     */
    public function prescriptions(): JsonResponse
    {
        return $this->success(
            data: PrescriptionResource::collection(
                $this->prescriptionRepo->allForAdmin()
            )
        );
    }
}
