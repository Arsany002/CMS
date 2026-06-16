<?php

namespace App\Http\Controllers\Assistant;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class DoctorController extends Controller
{
    use ApiResponse;

    /**
     * Return all active doctors belonging to the assistant's clinic.
     * Used by the appointment booking form to populate the doctor dropdown.
     */
    public function index(Request $request): JsonResponse
    {
        $doctors = User::where('clinic_id', $request->user()->clinic_id)
            ->where('role', UserRole::DOCTOR)
            ->where('is_active', true)
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->get();

        return $this->success(data: UserResource::collection($doctors));
    }
}
