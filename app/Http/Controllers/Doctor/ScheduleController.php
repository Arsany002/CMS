<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\DoctorSchedule;
use App\Repositories\ScheduleRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(private ScheduleRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $schedules = $this->repo->allForDoctor($request->user()->id);

        return $this->success(
            data: ScheduleResource::collection($schedules)
        );
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'doctor_id' => $request->user()->id,
            'clinic_id' => $request->user()->clinic_id,
            'is_active' => true,
        ]);

        $schedule = $this->repo->create($data);

        return $this->success(
            data: new ScheduleResource($schedule),
            message: 'Schedule created successfully',
            status: 201
        );
    }

    public function show(Request $request, DoctorSchedule $schedule): JsonResponse
    {
        abort_if($schedule->doctor_id !== $request->user()->id, 403, 'You do not own this schedule.');

        return $this->success(
            data: new ScheduleResource($schedule)
        );
    }

    public function update(StoreScheduleRequest $request, DoctorSchedule $schedule): JsonResponse
    {
        abort_if($schedule->doctor_id !== $request->user()->id, 403, 'You do not own this schedule.');

        $updated = $this->repo->update($schedule, $request->validated());

        return $this->success(
            data: new ScheduleResource($updated),
            message: 'Schedule updated successfully'
        );
    }

    public function destroy(Request $request, DoctorSchedule $schedule): JsonResponse
    {
        abort_if($schedule->doctor_id !== $request->user()->id, 403, 'You do not own this schedule.');

        $this->repo->delete($schedule);

        return $this->success(message: 'Schedule deleted successfully');
    }
}
