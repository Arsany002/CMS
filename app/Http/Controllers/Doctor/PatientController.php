<?php

namespace App\Http\Controllers\Doctor;

use App\Exceptions\ClinicScopeViolationException;
use App\Http\Controllers\Controller;
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
            $request->query('search'),
        );

        return $this->success(PatientResource::collection($patients));
    }

    public function show(Request $request, Patient $patient)
    {
        if ($patient->clinic_id !== $request->clinic_id) {
            throw new ClinicScopeViolationException();
        }

        return $this->success(new PatientResource($this->repo->getPatientById($patient->id, $request->clinic_id)));
    }
}
