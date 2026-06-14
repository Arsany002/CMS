<?php

namespace App\Http\Controllers\Assistant;

use App\Exceptions\ClinicScopeViolationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AvailableSlotsRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientRepository;
use App\Services\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AppointmentRepository $repo,
        private AppointmentService $service,
        private PatientRepository $patientRepo
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Fixed truncation: Default to today's date if not provided
        $filters = array_merge(
            ['date' => today()->format('Y-m-d')],
            $request->only(['status', 'date'])
        );

        // Security Update: Use the authenticated assistant's clinic_id
        $clinicId = $request->user()->clinic_id;
        $appointments = $this->repo->allForClinic($clinicId, $filters);

        return $this->success(
            data: AppointmentResource::collection($appointments)
        );
    }

   
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        // BR-07: Patient must belong to the same clinic (using Repository pattern)
        $patient = $this->patientRepo->getPatientById($request->patient_id);

        if ($patient->clinic_id !== $request->user()->clinic_id) {
            throw new ClinicScopeViolationException('Patient does not belong to this clinic.');
        }

        $data = array_merge($request->validated(), [
            'clinic_id' => $request->user()->clinic_id,
            'booked_by' => $request->user()->id,
        ]);

        $appointment = $this->service->book($data);

        return $this->success(
            data: new AppointmentResource($appointment),
            message: 'Appointment booked successfully',
            status: 201
        );
    }

    public function show(Appointment $appointment): JsonResponse
    {
        // Fixed truncation
        return $this->success(
            data: new AppointmentResource($this->repo->find($appointment->id))
        );
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $updated = $this->service->reschedule(array_merge($request->validated(), ['id' => $appointment->id]));

        return $this->success(
            data: new AppointmentResource($updated),
            message: 'Appointment rescheduled successfully'
        );
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->service->cancel(['id' => $appointment->id]);

        return $this->success(message: 'Appointment cancelled successfully');
    }

    public function availableSlots(AvailableSlotsRequest $request): JsonResponse
    {
        $slots = $this->service->getAvailableSlots($request->doctor_id, $request->date);

        return $this->success(data: ['slots' => $slots]);
    }
}
