<?php

namespace App\Http\Controllers\Doctor;

use App\Exceptions\ClinicScopeViolationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Prescription\StorePrescriptionRequest;
use App\Http\Requests\Prescription\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Repositories\PrescriptionRepository;
use App\Services\PrescriptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PrescriptionRepository $repo,
        private PrescriptionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            data: PrescriptionResource::collection(
                $this->repo->allForDoctor($request->user()->id)
            )
        );
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($request->appointment_id);

        // Explicitly separate core data from the one-to-many items array
        $data = $request->only(['appointment_id', 'diagnosis', 'notes']);
        $items = $request->input('items', []);

        $prescription = $this->service->create(
            $data,
            $items,
            $appointment,
            $request->user()->id
        );

        return $this->success(
            data: new PrescriptionResource($prescription),
            message: 'Prescription created successfully',
            status: 201
        );
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->doctor_id !== $request->user()->id) {
            throw new ClinicScopeViolationException('You do not have access to this prescription.');
        }

        return $this->success(
            data: new PrescriptionResource($this->repo->find($prescription->id))
        );
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->doctor_id !== $request->user()->id) {
            throw new ClinicScopeViolationException('You do not have access to this prescription.');
        }

        // Missing Data Added: Extract the items array to pass to the service
        $data = $request->only(['diagnosis', 'notes']);
        $items = $request->input('items', []);

        // Fixed truncated method call
        $updated = $this->service->update($prescription, $data, $items);

        return $this->success(
            data: new PrescriptionResource($updated),
            message: 'Prescription updated successfully'
        );
    }
}
