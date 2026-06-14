<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PrescriptionResource;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private DashboardService $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(
            data: $this->service->getStats(),
            message: 'Dashboard statistics'
        );
    }

    public function appointments(Request $request): JsonResponse
    {
        $filters = $request->only(['clinic_id', 'doctor_id', 'status', 'date']);

        return $this->success(
            data: AppointmentResource::collection(
                $this->service->getAllAppointments($filters)
            )
        );
    }

    public function prescriptions(): JsonResponse
    {
        return $this->success(
            data: PrescriptionResource::collection(
                $this->service->getAllPrescriptions()
            )
        );
    }
}
