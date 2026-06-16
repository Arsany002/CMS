<?php

namespace App\Services;

use App\Models\Clinic;
use App\Repositories\ClinicRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClinicService
{
    public function __construct(
        private ClinicRepository $repo,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repo->getAllClinics($perPage);
    }

    public function active(): Collection
    {
        return $this->repo->getActiveClinics();
    }

    public function create(array $data): Clinic
    {
        return $this->repo->createClinic($data);
    }

    public function find(string $id): Clinic
    {
        return $this->repo->getClinicById($id);
    }

    public function update(string $id, array $data): Clinic
    {
        return $this->repo->updateClinicById($id, $data);
    }

    public function toggle(string $id): Clinic
    {
        return $this->repo->toggleActiveById($id);
    }
}
