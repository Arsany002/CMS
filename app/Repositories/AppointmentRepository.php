<?php

namespace App\Repositories;

use App\Models\Appointment;
use Illuminate\Pagination\LengthAwarePaginator;

class AppointmentRepository
{
    public function allForDoctor(int $doctorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::select(['id', 'doctor_id', 'patient_id', 'booked_by', 'appointment_date', 'start_time', 'end_time', 'status', 'notes'])
            ->with([
                'patient:id,name,phone',
                'bookedBy:id,name',
            ])
            ->where('doctor_id', $doctorId)
            ->when(isset($filters['date']), fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->paginate($perPage);
    }

    public function allForClinic(int $clinicId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::select(['id', 'clinic_id', 'doctor_id', 'patient_id', 'booked_by', 'appointment_date', 'start_time', 'end_time', 'status', 'notes'])
            ->with([
                'patient:id,name,phone',
                'doctor:id,name',
                'bookedBy:id,name',
            ])
            ->where('clinic_id', $clinicId)
            ->when(isset($filters['date']), fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
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
            ->when(isset($filters['clinic_id']), fn($q) => $q->where('clinic_id', $filters['clinic_id']))
            ->when(isset($filters['doctor_id']), fn($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date']), fn($q) => $q->whereDate('appointment_date', $filters['date']))
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

    public function findForDoctor(string $id, string $doctorId): Appointment
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
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('start_time')
            ->toArray();
    }
}
