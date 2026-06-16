<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Endpoints that exist ONLY in local/testing environments.
 * Never exposed in production — see routes/api.php for the guard.
 */
class TestingController extends Controller
{
    use ApiResponse;

    /**
     * Seed a one-time Google exchange code for a given user_id and return it.
     * Allows Playwright tests to simulate the post-callback exchange flow
     * without needing real Google OAuth credentials.
     */
    public function seedGoogleExchangeCode(Request $request): JsonResponse
    {
        $request->validate(['user_id' => ['required', 'string', 'exists:users,id']]);

        $code = Str::uuid()->toString();
        Cache::put("google_exchange:{$code}", $request->validated('user_id'), now()->addMinutes(5));

        return $this->success(data: ['code' => $code], message: 'Exchange code seeded');
    }
}
