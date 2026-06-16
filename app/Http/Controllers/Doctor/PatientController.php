<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
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

    public function show(Request $request, string $patient)
    {
        return $this->success(new PatientResource($this->service->findForClinic($patient, $request->clinic_id)));
    }
}
