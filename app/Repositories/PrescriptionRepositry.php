<?php

namespace App\Repositories;

use App\Models\Prescription;
use Illuminate\Pagination\LengthAwarePaginator;

class PrescriptionRepository
{
    /**
     * Get paginated prescriptions for a specific doctor.
     */
    public function allForDoctor(int $doctorId, int $perPage = 15): LengthAwarePaginator
    {
        return Prescription::with(['patient', 'items'])
            ->where('doctor_id', $doctorId)
            ->paginate($perPage);
    }

    /**
     * Get all paginated prescriptions across the system (Super Admin view).
     */
    public function allForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Prescription::with(['patient', 'doctor', 'clinic', 'items'])
            ->paginate($perPage);
    }

    /**
     * Find a specific prescription by ID.
     */
    public function find(int $id): Prescription
    {
        return Prescription::with(['patient', 'items', 'appointment'])->findOrFail($id);
    }

    /**
     * Create a new prescription along with its medicine items.
     */
    public function create(array $data, array $items): Prescription
    {
        $prescription = Prescription::create($data);

        $prescription->items()->createMany($items);

        return $prescription->load('items');
    }

    /**
     * Update an existing prescription and completely replace its medicine items.
     */
    public function update(Prescription $prescription, array $data, array $items): Prescription
    {
        $prescription->update($data);

        // Delete the old medicine items and insert the new ones
        $prescription->items()->delete();
        $prescription->items()->createMany($items);

        return $prescription->load('items');
    }
}
