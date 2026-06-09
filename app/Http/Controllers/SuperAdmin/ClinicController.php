<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\StoreClinicRequest;
use App\Http\Requests\Clinic\UpdateClinicRequest;
use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use App\Repositories\ClinicRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ClinicRepository $repo
    ) {}

    public function index(): JsonResponse
    {
        // Changed from all() to getAllClinics()
        return $this->success(
            data: ClinicResource::collection($this->repo->getAllClinics())
        );
    }

    public function store(StoreClinicRequest $request): JsonResponse
    {
        // Changed from create() to createClinic()
        $clinic = $this->repo->createClinic($request->validated());

        return $this->success(
            data: new ClinicResource($clinic),
            message: 'Clinic created',
            status: 201
        );
    }

    public function show(Clinic $clinic): JsonResponse
    {
        // Changed from find() to getClinicById() and passed the ID
        return $this->success(
            data: new ClinicResource($this->repo->getClinicById($clinic->id))
        );
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic): JsonResponse
    {
        // Changed from update() to updateClinic() and passed the ID + Data
        $updated = $this->repo->updateClinic($clinic->id, $request->validated());

        return $this->success(
            data: new ClinicResource($updated),
            message: 'Clinic updated'
        );
    }

    public function toggle(Clinic $clinic): JsonResponse
    {
        // Changed from toggle() to toggleClinicStatus() and passed the ID
        $updated = $this->repo->toggleClinicStatus($clinic->id);

        return $this->success(
            data: new ClinicResource($updated),
            message: 'Clinic status toggled'
        );
    }
}
