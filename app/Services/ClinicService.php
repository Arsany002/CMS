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
        $clinic->is_active = !$clinic->is_active;
        return $this->repo->saveClinic($clinic);
    }
}
