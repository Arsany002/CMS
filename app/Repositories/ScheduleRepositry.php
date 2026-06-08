<?php

namespace App\Repositories;

use App\Models\DoctorSchedule;
use Illuminate\Database\Eloquent\Collection;

class ScheduleRepository
{
    /**
     * Get all schedule entries for a specific doctor.
     */
    public function allForDoctor(int $doctorId): Collection
    {
        return DoctorSchedule::where('doctor_id', $doctorId)->get();
    }

    /**
     * Find a specific schedule entry by ID.
     */
    public function find(int $id): DoctorSchedule
    {
        return DoctorSchedule::findOrFail($id);
    }

    /**
     * Create a new schedule entry.
     */
    public function create(array $data): DoctorSchedule
    {
        return DoctorSchedule::create($data);
    }

    /**
     * Update an existing schedule entry.
     */
    public function update(DoctorSchedule $schedule, array $data): DoctorSchedule
    {
        $schedule->update($data);

        return $schedule;
    }

    /**
     * Delete a schedule entry.
     */
    public function delete(DoctorSchedule $schedule): void
    {
        $schedule->delete();
    }

    /**
     * Find an active schedule for a specific doctor on a specific day of the week.
     */
    public function findForDayAndDoctor(int $doctorId, int $dayOfWeek): ?DoctorSchedule
    {
        return DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
    }
}
