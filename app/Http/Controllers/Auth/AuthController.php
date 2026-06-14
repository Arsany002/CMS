<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        // The LoginRequest already validated the email and password
        $result = $this->authService->login($request->email, $request->password);

        return $this->success(
            data: [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            message: 'Login successful'
        );
    }
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->success(
            data: [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            message: 'User registered successfully',
            status: 201 // 201 Created
        );
    }

    public function logout(Request $request): JsonResponse
    {
        // Revokes the Passport token for the currently authenticated user
        $this->authService->logout($request->user());

        return $this->success(message: 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            data: new UserResource($this->authService->me($request->user()->id))
        );
    }
}
