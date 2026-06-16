<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescription\StorePrescriptionRequest;
use App\Http\Requests\Prescription\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Services\PrescriptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PrescriptionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            data: PrescriptionResource::collection(
                $this->service->allForDoctor($request->user()->id)
            )
        );
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $data  = $request->only(['appointment_id', 'diagnosis', 'notes']);
        $items = $request->input('items', []);

        $prescription = $this->service->create(
            $data,
            $items,
            $request->appointment_id,
            $request->user()->id
        );

        return $this->success(
            data: new PrescriptionResource($prescription),
            message: 'Prescription created successfully',
            status: 201
        );
    }

    public function show(Request $request, string $prescription): JsonResponse
    {
        return $this->success(
            data: new PrescriptionResource($this->service->findForDoctor($prescription, $request->user()->id))
        );
    }

    public function update(UpdatePrescriptionRequest $request, string $prescription): JsonResponse
    {
        $data = $request->only(['diagnosis', 'notes']);
        $items = $request->input('items', []);

        $updated = $this->service->updateForDoctor($prescription, $request->user()->id, $data, $items);

        return $this->success(
            data: new PrescriptionResource($updated),
            message: 'Prescription updated successfully'
        );
    }
}
