<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Services\ScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(private ScheduleService $service) {}

    public function index(Request $request): JsonResponse
    {
        $schedules = $this->service->allForDoctor($request->user()->id);

        return $this->success(
            data: ScheduleResource::collection($schedules)
        );
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $schedule = $this->service->createForDoctor(
            $request->validated(),
            $request->user()->id,
            $request->user()->clinic_id
        );

        return $this->success(
            data: new ScheduleResource($schedule),
            message: 'Schedule created successfully',
            status: 201
        );
    }

    public function show(Request $request, string $schedule): JsonResponse
    {
        return $this->success(
            data: new ScheduleResource($this->service->findForDoctor($schedule, $request->user()->id))
        );
    }

    public function update(StoreScheduleRequest $request, string $schedule): JsonResponse
    {
        $updated = $this->service->updateForDoctor($schedule, $request->user()->id, $request->validated());

        return $this->success(
            data: new ScheduleResource($updated),
            message: 'Schedule updated successfully'
        );
    }

    public function destroy(Request $request, string $schedule): JsonResponse
    {
        $this->service->deleteForDoctor($schedule, $request->user()->id);

        return $this->success(message: 'Schedule deleted successfully');
    }
}
