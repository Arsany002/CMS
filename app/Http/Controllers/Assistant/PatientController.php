<?php

namespace App\Http\Controllers\Assistant;

use App\Exceptions\ClinicScopeViolationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use ApiResponse;

    public function __construct(private PatientRepository $repo) {}

    public function index(Request $request)
    {
        $patients = $this->repo->allForClinic(
            $request->clinic_id,
            $request->query('search') ?: null,
        );

        return $this->success(PatientResource::collection($patients));
    }

    public function store(StorePatientRequest $request)
    {
        $data = array_merge($request->validated(), [
            'clinic_id' => $request->clinic_id,
        ]);

        $patient = $this->repo->createPatient($data);

        return $this->success(new PatientResource($patient), 'Patient created', 201);
    }

    public function show(Request $request, Patient $patient)
    {
        if ($patient->clinic_id !== $request->clinic_id) {
            throw new ClinicScopeViolationException();
        }

        return $this->success(new PatientResource($this->repo->getPatientById($patient->id, $request->clinic_id)));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        if ($patient->clinic_id !== $request->clinic_id) {
            throw new ClinicScopeViolationException();
        }

        $updated = $this->repo->updatePatient($patient->id, $request->validated(), $request->clinic_id);

        return $this->success(new PatientResource($updated), 'Patient updated');
    }
}
