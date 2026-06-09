<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Services\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AppointmentRepository $repo,
        private AppointmentService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Fixed the truncated '$request->on...' 
        // Assuming you wanted to pass specific filters like date or status.
        $filters = $request->only(['date', 'status']);

        $appointments = $this->repo->allForDoctor($request->user()->id, $filters);

        return $this->success(
            data: AppointmentResource::collection($appointments)
        );
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        // Security check: Ensure the logged-in doctor actually owns this appointment
        abort_if($appointment->doctor_id !== $request->user()->id, 403, 'Unauthorized access to this appointment.');

        // Fixed the truncated repo call and added the missing closing brackets
        return $this->success(
            data: new AppointmentResource($this->repo->find($appointment->id))
        );
    }

    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        // Security check: Ensure the logged-in doctor actually owns this appointment
        abort_if($appointment->doctor_id !== $request->user()->id, 403, 'Unauthorized access to this appointment.');

        // Simple validation directly in the controller (acceptable for single fields)
        $request->validate([
            'status' => ['required', 'in:confirmed,cancelled,completed'],
        ]);

        $updated = $this->service->updateStatus($appointment, $request->status);

        return $this->success(
            data: new AppointmentResource($updated),
            message: 'Status updated'
        );
    }
}
