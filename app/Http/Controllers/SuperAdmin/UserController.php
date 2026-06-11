<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\updateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(private UserRepository $repo) {}

    /**
     * List all users, optionally scoped to a clinic.
     */
    public function index(): JsonResponse
    {
        return $this->success(
            data: UserResource::collection($this->repo->getAllUsers())
        );
    }

    /**
     * Create a new user (doctor or assistant) and assign them to a clinic.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'password' => bcrypt($request->password),
        ]);

        $user = $this->repo->createUser($data);

        return $this->success(
            data: new UserResource($user),
            message: 'User created successfully',
            status: 201
        );
    }

    /**
     * Show a single user by ID.
     */
    public function show(User $user): JsonResponse
    {
        return $this->success(
            data: new UserResource($this->repo->getUserById($user->id))
        );
    }

    /**
     * Update an existing user's details.
     */
    public function update(updateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        // Only re-hash the password if a new one was explicitly provided
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $updated = $this->repo->updateUser($user->id, $data);

        return $this->success(
            data: new UserResource($updated),
            message: 'User updated successfully'
        );
    }

    /**
     * Toggle a user's active/inactive status.
     */
    public function toggle(User $user): JsonResponse
    {
        $updated = $this->repo->toggleUserStatus($user->id);

        return $this->success(
            data: new UserResource($updated),
            message: 'User status toggled successfully'
        );
    }
    public function updateRole(Request $request, User $user): JsonResponse
    {
        // 1. Validate the incoming role
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:super_admin,doctor,assistant'],
        ]);

        // 2. Prevent a Super Admin from accidentally demoting themselves
        if ($request->user()->id === $user->id && $validated['role'] !== 'super_admin') {
            return $this->error(
                message: 'You cannot change your own Super Admin role.',
                status: 403
            );
        }

        // 3. Update the role in the database
        $user->update(['role' => $validated['role']]);

        // 4. Return the standard API response
        return $this->success(
            data: $user,
            message: "User role successfully updated to {$validated['role']}."
        );
    }
}
