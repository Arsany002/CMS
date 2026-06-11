<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
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
        $filters = $request->only(['date', 'status']);

        $appointments = $this->repo->allForDoctor($request->user()->id, $filters);

        return $this->success(
            data: AppointmentResource::collection($appointments)
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $appointment = $this->repo->findForDoctor($id, $request->user()->id);

        return $this->success(
            data: new AppointmentResource($appointment)
        );
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:confirmed,cancelled,completed'],
        ]);

        $appointment = $this->repo->findForDoctor($id, $request->user()->id);
        $updated = $this->service->updateStatus($appointment, $request->status);

        return $this->success(
            data: new AppointmentResource($updated),
            message: 'Status updated'
        );
    }
}
