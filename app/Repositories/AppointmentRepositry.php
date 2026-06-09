<?php
namespace App\Repositories;
use App\Models\Appointment;
use Illuminate\Pagination\LengthAwarePaginator;

class AppointmentRepository
{
    public function allForDoctor(int $doctorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::with(['patient', 'bookedBy'])
            ->where('doctor_id', $doctorId)
            ->when(isset($filters['date']), fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->paginate($perPage);
    }

    /**
     * Get appointments for a specific clinic (Assistant view).
     */
    public function allForClinic(int $clinicId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::with(['patient', 'doctor', 'bookedBy'])
            ->where('clinic_id', $clinicId)
            ->when(isset($filters['date']), fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->paginate($perPage);
    }

    /**
     * Get all appointments across all clinics (Super Admin view).
     */
    public function allForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Appointment::with(['patient', 'doctor', 'clinic'])
            ->when(isset($filters['clinic_id']), fn($q) => $q->where('clinic_id', $filters['clinic_id']))
            ->when(isset($filters['doctor_id']), fn($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date']), fn($q) => $q->whereDate('appointment_date', $filters['date']))
            ->paginate($perPage);
    }

    /**
     * Find a specific appointment by ID.
     */
    public function find(int $id): Appointment
    {
        return Appointment::with(['patient', 'doctor', 'prescription.items'])->findOrFail($id);
    }

    /**
     * Create a new appointment.
     */
    public function create(array $data): Appointment
    {
        return Appointment::create($data);
    }
    public function update(int $id, array $data): Appointment
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update($data);
        return $appointment;
    }
    public function isSlotTaken(int $doctorId, string $date, string $startTime, ?int $excludeId = null): bool
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('start_time', $startTime)
            ->where('status', '!=', 'cancelled')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
    public function getBookedTimeSlots(int $doctorId, string $date): array
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled') // Ignore cancelled appointments
            ->pluck('start_time')
            ->toArray();
    }
}