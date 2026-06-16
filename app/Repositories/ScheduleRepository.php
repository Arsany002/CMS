<?php

namespace App\Repositories;

use App\Models\DoctorSchedule;
use Illuminate\Database\Eloquent\Collection;

class ScheduleRepository
{
    /**
     * Get all schedule entries for a specific doctor.
     */
    public function allForDoctor(string $doctorId): Collection
    {
        return DoctorSchedule::select(['id', 'doctor_id', 'clinic_id', 'day_of_week', 'start_time', 'end_time', 'slot_duration', 'is_active'])
            ->where('doctor_id', $doctorId)
            ->get();
    }

    /**
     * Find a specific schedule entry by ID.
     */
    public function find(string $id): DoctorSchedule
    {
        return DoctorSchedule::findOrFail($id);
    }

    public function findForDoctor(string $id, string $doctorId): DoctorSchedule
    {
        return DoctorSchedule::where('id', $id)
            ->where('doctor_id', $doctorId)
            ->firstOrFail();
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

    public function updateForDoctor(string $id, string $doctorId, array $data): DoctorSchedule
    {
        $schedule = $this->findForDoctor($id, $doctorId);
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

    public function deleteForDoctor(string $id, string $doctorId): void
    {
        $this->findForDoctor($id, $doctorId)->delete();
    }

    /**
     * Find an active schedule for a specific doctor on a specific day of the week.
     */
    public function findForDayAndDoctor(string $doctorId, int $dayOfWeek): ?DoctorSchedule
    {
        return DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
    }
}
