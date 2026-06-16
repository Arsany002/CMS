<?php

namespace App\Http\Controllers\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    use ApiResponse;

    public function __construct(private UserRepository $userRepo) {}

    /**
     * Return all active doctors belonging to the assistant's clinic.
     * Used by the appointment booking form to populate the doctor dropdown.
     */
    public function index(Request $request): JsonResponse
    {
        $doctors = $this->userRepo->getDoctorsForClinic($request->user()->clinic_id);

        return $this->success(data: UserResource::collection($doctors));
    }
}
