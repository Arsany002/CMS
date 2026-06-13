<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'clinic.scope' => App\Http\Middleware\ClinicScopeMiddleware::class,
            'role' => App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\App\Exceptions\AppointmentConflictException $e, Request $request) {
            return response()->json([
                'error'   => 'appointment_conflict',
                'message' => 'The doctor already has an appointment at this time.',
            ], 409);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            return response()->json(['error' => 'unauthenticated', 'message' => 'Token invalid or expired.'], 401);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            return response()->json([
                'error'   => 'validation_failed',
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            return response()->json(['error' => 'forbidden', 'message' => 'Insufficient permissions.'], 403);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        });
    })->create();
