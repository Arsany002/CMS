<?php

namespace App\Http\Controllers\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AvailableSlotsRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AppointmentService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Default to today; an empty string clears the date filter (shows all).
        $dateParam = $request->query('date');
        $filters   = [
            'date'   => (isset($dateParam) && $dateParam !== '') ? $dateParam : today()->format('Y-m-d'),
            'status' => $request->query('status', ''),
        ];

        $appointments = $this->service->allForClinic($request->user()->clinic_id, $filters);

        return $this->success(
            data: AppointmentResource::collection($appointments)
        );
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'clinic_id' => $request->user()->clinic_id,
            'booked_by' => $request->user()->id,
        ]);

        // BR-07 (patient belongs to this clinic) is enforced inside AppointmentService::book()
        $appointment = $this->service->book($data);

        return $this->success(
            data: new AppointmentResource($appointment),
            message: 'Appointment booked successfully',
            status: 201
        );
    }

    public function show(string $appointment): JsonResponse
    {
        return $this->success(
            data: new AppointmentResource($this->service->find($appointment))
        );
    }

    public function update(UpdateAppointmentRequest $request, string $appointment): JsonResponse
    {
        $updated = $this->service->reschedule(array_merge($request->validated(), ['id' => $appointment]));

        return $this->success(
            data: new AppointmentResource($updated),
            message: 'Appointment rescheduled successfully'
        );
    }

    public function destroy(string $appointment): JsonResponse
    {
        $this->service->cancel(['id' => $appointment]);

        return $this->success(message: 'Appointment cancelled successfully');
    }

    public function availableSlots(AvailableSlotsRequest $request): JsonResponse
    {
        $slots = $this->service->getAvailableSlots($request->doctor_id, $request->date);

        return $this->success(data: ['slots' => $slots]);
    }
}
