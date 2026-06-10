<?php

namespace App\Http\Controllers\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Repositories\AppointmentRepository;
use App\Services\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AppointmentRepository $repo,
        private AppointmentService $service
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

    /**
     * @OA\Post(
     *     path="/api/v1/assistant/appointments",
     *     tags={"Appointments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreAppointmentRequest")),
     *     @OA\Response(response=201, description="Appointment created", @OA\JsonContent(ref="#/components/schemas/AppointmentResource")),
     *     @OA\Response(response=409, description="Appointment conflict")
     * )
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        // BR-07: Patient must belong to the same clinic
        $patient = Patient::findOrFail($request->patient_id);

        // Fixed truncation & Security Update: Compare against the user's clinic_id
        abort_if(
            $patient->clinic_id !== $request->user()->clinic_id,
            403,
            'Patient does not belong to this clinic.'
        );

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
        $updated = $this->service->reschedule($appointment, $request->validated());

        return $this->success(
            data: new AppointmentResource($updated),
            message: 'Appointment rescheduled successfully'
        );
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->service->cancel($appointment);

        return $this->success(message: 'Appointment cancelled successfully');
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => ['required', 'exists:users,id'],
            'date'      => ['required', 'date', 'after_or_equal:today'],
        ]);

        // Fixed truncation
        $slots = $this->service->getAvailableSlots($request->doctor_id, $request->date);

        return $this->success(data: ['slots' => $slots]);
    }
}
