<?php

namespace App\Services;

use App\Models\Clinic;
use App\Repositories\ClinicRepository;

class ClinicService
{
    public function __construct(
        private ClinicRepository $repo,
    ) {}

    public function toggle(Clinic $clinic): Clinic
    {
        return $this->repo->toggleActive($clinic);
    }
}
