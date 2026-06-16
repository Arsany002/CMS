<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ExchangeGoogleCodeRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
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
            status: 201
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(message: 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            data: new UserResource($this->authService->me($request->user()->id))
        );
    }

    // ─── Google OAuth ────────────────────────────────────────────────────────────

    /**
     * Redirect the browser to Google's OAuth consent screen.
     * Optional query params: role (doctor|assistant), clinic_id.
     * If role is provided, backend validates it before redirecting.
     */
    public function googleRedirect(Request $request): RedirectResponse
    {
        return $this->authService->buildGoogleRedirect(
            $request->query('role'),
            $request->query('clinic_id'),
        );
    }

    /**
     * Google redirects back here. We resolve the user, create a one-time
     * exchange code, and forward the browser to the React frontend.
     *
     * Success:  {FRONTEND_URL}/auth/google/callback?code=ONE_TIME_CODE
     * Error:    {FRONTEND_URL}/auth/google/callback?error=MESSAGE_KEY
     */
    public function googleCallback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://127.0.0.1:5174')), '/');
        $callbackBase = $frontendUrl . '/auth/google/callback';

        $stateKey = $request->query('state', '');

        try {
            $exchangeCode = $this->authService->handleGoogleCallback($stateKey);
            return redirect($callbackBase . '?code=' . urlencode($exchangeCode));
        } catch (\RuntimeException $e) {
            $errorKey = match ($e->getMessage()) {
                'registration_required'                 => 'registration_required',
                'OAuth state is invalid or has expired.' => 'state_invalid',
                default                                 => 'google_error',
            };
            return redirect($callbackBase . '?error=' . $errorKey);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect($callbackBase . '?error=validation_failed');
        } catch (\App\Exceptions\AccountDeactivatedException) {
            return redirect($callbackBase . '?error=account_deactivated');
        } catch (\Throwable) {
            return redirect($callbackBase . '?error=google_error');
        }
    }

    /**
     * Exchange the one-time code for a Passport access token + user object.
     * Called by the React GoogleCallback page via fetch/Axios.
     */
    public function googleExchange(ExchangeGoogleCodeRequest $request): JsonResponse
    {
        $result = $this->authService->exchangeGoogleCode($request->validated('code'));

        return $this->success(
            data: [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            message: 'Google sign-in successful'
        );
    }
}
