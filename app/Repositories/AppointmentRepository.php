<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Pagination\LengthAwarePaginator;

class AppointmentRepository
{
    public function allForDoctor(string $doctorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::select(['id', 'doctor_id', 'patient_id', 'booked_by', 'appointment_date', 'start_time', 'end_time', 'status', 'notes'])
            ->with([
                'patient:id,name,phone',
                'bookedBy:id,name',
            ])
            ->where('doctor_id', $doctorId)
            ->when(!empty($filters['date']),   fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->paginate($perPage);
    }

    public function allForClinic(string $clinicId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::select(['id', 'clinic_id', 'doctor_id', 'patient_id', 'booked_by', 'appointment_date', 'start_time', 'end_time', 'status', 'notes'])
            ->with([
                'patient:id,name,phone',
                'doctor:id,name',
                'bookedBy:id,name',
            ])
            ->where('clinic_id', $clinicId)
            ->when(!empty($filters['date']),   fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->paginate($perPage);
    }

    public function allForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::select(['id', 'clinic_id', 'doctor_id', 'patient_id', 'booked_by', 'appointment_date', 'start_time', 'end_time', 'status', 'notes'])
            ->with([
                'patient:id,name,phone',
                'doctor:id,name',
                'clinic:id,name',
            ])
            ->when(!empty($filters['clinic_id']), fn($q) => $q->where('clinic_id', $filters['clinic_id']))
            ->when(!empty($filters['doctor_id']), fn($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when(!empty($filters['status']),    fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['date']),      fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->paginate($perPage);
    }

    public function find(string $id): Appointment
    {
        return Appointment::select(['id', 'clinic_id', 'doctor_id', 'patient_id', 'booked_by', 'appointment_date', 'start_time', 'end_time', 'status', 'notes'])
            ->with([
                'patient:id,name,phone,date_of_birth,gender',
                'doctor:id,name',
                'prescription',
            ])
            ->findOrFail($id);
    }

    public function findForDoctor(string $id, string $doctorId): Appointment  // doctorId is UUID string
    {
        return Appointment::select(['id', 'doctor_id', 'patient_id', 'appointment_date', 'start_time', 'end_time', 'status', 'notes'])
            ->with([
                'patient:id,name,phone,date_of_birth,gender',
                'prescription',
            ])
            ->where('doctor_id', $doctorId)
            ->findOrFail($id);
    }

    public function create(array $data): Appointment
    {
        return Appointment::create($data);
    }

    public function update(string $id, array $data): Appointment
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update($data);
        return $appointment;
    }

    public function isSlotTaken(string $doctorId, string $date, string $startTime, ?string $excludeId = null): bool
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('start_time', $startTime)
            ->where('status', '!=', 'cancelled')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function getBookedTimeSlots(string $doctorId, string $date): array
    {
        // Normalise to H:i so the comparison in getAvailableSlots() works
        // regardless of whether the DB driver returns HH:MM or HH:MM:SS.
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('start_time')
            ->map(fn(string $t) => substr($t, 0, 5))
            ->toArray();
    }
    public function getStatus(string $id): AppointmentStatus
    {
        return Appointment::where('id', $id)->value('status');
    }

    public function count(): int
    {
        return Appointment::count();
    }

    public function countToday(): int
    {
        return Appointment::whereDate('appointment_date', today())->count();
    }

    public function getUpcomingForReminders(int $withinMinutes, string $reminderColumn): \Illuminate\Database\Eloquent\Collection
    {
        return Appointment::with(['doctor:id,name,email', 'patient:id,name'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull($reminderColumn)
            ->whereRaw(
                'ADDTIME(appointment_date, start_time) BETWEEN ? AND ?',
                [now()->toDateTimeString(), now()->addMinutes($withinMinutes)->toDateTimeString()]
            )
            ->get();
    }
}
