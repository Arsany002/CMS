<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\StoreClinicRequest;
use App\Http\Requests\Clinic\UpdateClinicRequest;
use App\Http\Resources\ClinicResource;
use App\Services\ClinicService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ClinicService $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(
            data: ClinicResource::collection($this->service->paginate())
        );
    }

    public function publicIndex(): JsonResponse
    {
        return $this->success(data: $this->service->active());
    }

    public function store(StoreClinicRequest $request): JsonResponse
    {
        $clinic = $this->service->create($request->validated());

        return $this->success(
            data: new ClinicResource($clinic),
            message: 'Clinic created',
            status: 201
        );
    }

    public function show($id): JsonResponse
    {
        $clinic = $this->service->find($id);

        return $this->success(
            data: new ClinicResource($clinic)
        );
    }

    public function update(UpdateClinicRequest $request, string $clinic): JsonResponse
    {
        $updated = $this->service->update($clinic, $request->validated());

        return $this->success(
            data: new ClinicResource($updated),
            message: 'Clinic updated'
        );
    }

    public function toggle(string $clinic): JsonResponse
    {
        $updated = $this->service->toggle($clinic);

        return $this->success(
            data: new ClinicResource($updated),
            message: 'Clinic status toggled'
        );
    }
}
