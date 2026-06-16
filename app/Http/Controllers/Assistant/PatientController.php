<?php

namespace App\Http\Controllers\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use ApiResponse;

    public function __construct(private PatientService $service) {}

    public function index(Request $request)
    {
        $patients = $this->service->allForClinic(
            $request->clinic_id,
            $request->query('search') ?: null,
        );

        return $this->success(PatientResource::collection($patients));
    }

    public function store(StorePatientRequest $request)
    {
        $patient = $this->service->createForClinic($request->validated(), $request->clinic_id);

        return $this->success(new PatientResource($patient), 'Patient created', 201);
    }

    public function show(Request $request, string $patient)
    {
        return $this->success(new PatientResource($this->service->findForClinic($patient, $request->clinic_id)));
    }

    public function update(UpdatePatientRequest $request, string $patient)
    {
        $updated = $this->service->updateForClinic($patient, $request->validated(), $request->clinic_id);

        return $this->success(new PatientResource($updated), 'Patient updated');
    }
}
