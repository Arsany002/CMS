<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\StoreClinicRequest;
use App\Http\Requests\Clinic\UpdateClinicRequest;
use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use App\Repositories\ClinicRepository;
use App\Services\ClinicService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ClinicRepository $repo,
        private ClinicService $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(
            data: ClinicResource::collection($this->repo->getAllClinics())
        );
    }

    public function store(StoreClinicRequest $request): JsonResponse
    {
        $clinic = $this->repo->createClinic($request->validated());

        return $this->success(
            data: new ClinicResource($clinic),
            message: 'Clinic created',
            status: 201
        );
    }

    public function show($id): JsonResponse
    {
        $clinic = $this->repo->getClinicById($id);

        return $this->success(
            data: new ClinicResource($clinic)
        );
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic): JsonResponse
    {
        $updated = $this->repo->updateClinic($clinic, $request->validated());

        return $this->success(
            data: new ClinicResource($updated),
            message: 'Clinic updated'
        );
    }

    public function toggle(Clinic $clinic): JsonResponse
    {
        $updated = $this->service->toggle($clinic);

        return $this->success(
            data: new ClinicResource($updated),
            message: 'Clinic status toggled'
        );
    }
}
