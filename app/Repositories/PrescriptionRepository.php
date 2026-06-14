<?php

namespace App\Repositories;

use App\Models\Prescription;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrescriptionRepository
{
    public function allForDoctor(string $doctorId, int $perPage = 15): LengthAwarePaginator
    {
        return Prescription::select(['id', 'appointment_id', 'doctor_id', 'patient_id', 'clinic_id', 'diagnosis', 'notes', 'created_at'])
            ->with([
                'patient:id,name,phone',
                'items:id,prescription_id,medicine_name,dosage,frequency,duration',
            ])
            ->where('doctor_id', $doctorId)
            ->paginate($perPage);
    }

    public function allForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Prescription::select(['id', 'appointment_id', 'doctor_id', 'patient_id', 'clinic_id', 'diagnosis', 'notes', 'created_at'])
            ->with([
                'patient:id,name,phone',
                'doctor:id,name',
                'clinic:id,name',
                'items:id,prescription_id,medicine_name,dosage,frequency,duration',
            ])
            ->paginate($perPage);
    }

    public function find(string $id): Prescription
    {
        return Prescription::select(['id', 'appointment_id', 'doctor_id', 'patient_id', 'clinic_id', 'diagnosis', 'notes', 'created_at'])
            ->with([
                'patient:id,name,phone',
                'items:id,prescription_id,medicine_name,dosage,frequency,duration,notes',
                'appointment:id,appointment_date,start_time,status',
            ])
            ->findOrFail($id);
    }

    public function create(array $data, array $items): Prescription
    {
        return DB::transaction(function () use ($data, $items) {
            $prescription = Prescription::create($data);
            $prescription->items()->createMany($items);
            return $prescription->load('items');
        });
    }

    public function update(Prescription $prescription, array $data, array $items): Prescription
    {
        return DB::transaction(function () use ($prescription, $data, $items) {
            $prescription->update($data);
            $prescription->items()->delete();
            $prescription->items()->createMany($items);
            return $prescription->load('items');
        });
    }

    public function count(): int
    {
        return Prescription::count();
    }
}
