<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRoleRequest;
use App\Http\Requests\User\updateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserService $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(
            data: UserResource::collection($this->service->paginate())
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());

        return $this->success(
            data: new UserResource($user),
            message: 'User created successfully',
            status: 201
        );
    }

    public function show(string $user): JsonResponse
    {
        return $this->success(
            data: new UserResource($this->service->findById($user))
        );
    }

    public function update(updateUserRequest $request, string $user): JsonResponse
    {
        $updated = $this->service->update($user, $request->validated());

        return $this->success(
            data: new UserResource($updated),
            message: 'User updated successfully'
        );
    }

    public function toggle(string $user): JsonResponse
    {
        $updated = $this->service->toggleStatus($user);

        return $this->success(
            data: new UserResource($updated),
            message: 'User status toggled successfully'
        );
    }

    public function updateRole(UpdateUserRoleRequest $request, string $user): JsonResponse
    {
        $updated = $this->service->updateRole($request->user()->id, $user, $request->validated('role'));

        return $this->success(
            data: new UserResource($updated),
            message: "User role successfully updated to {$updated->role->value}."
        );
    }
}
