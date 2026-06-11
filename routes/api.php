<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SuperAdmin\ClinicController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\Doctor\ScheduleController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Doctor\PrescriptionController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Assistant\PatientController as AssistantPatientController;
use App\Http\Controllers\Assistant\AppointmentController as AssistantAppointmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // ─── Auth ───────────────────────────────────────────────────────────────────
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);

        Route::middleware(['auth:api', 'throttle:api'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // ─── Super Admin ─────────────────────────────────────────────────────────────
    Route::middleware(['auth:api', 'throttle:api', 'role:super_admin'])->prefix('super-admin')->group(function () {
        Route::apiResource('clinics', ClinicController::class)->except(['destroy']);
        Route::patch('clinics/{clinic}/toggle', [ClinicController::class, 'toggle']);

        Route::apiResource('users', UserController::class)->except(['destroy']);
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle']);

        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('appointments', [DashboardController::class, 'appointments']);
        Route::get('prescriptions', [DashboardController::class, 'prescriptions']);
    });

    // ─── Doctor ──────────────────────────────────────────────────────────────────
    Route::middleware(['auth:api', 'throttle:api', 'role:doctor', 'clinic.scope'])->prefix('doctor')->group(function () {
        Route::apiResource('schedules', ScheduleController::class);

        Route::get('appointments', [DoctorAppointmentController::class, 'index']);
        Route::get('appointments/{id}', [DoctorAppointmentController::class, 'show']);
        Route::patch('appointments/{id}/status', [DoctorAppointmentController::class, 'updateStatus']);

        Route::apiResource('prescriptions', PrescriptionController::class)->except(['destroy']);

        Route::get('patients', [DoctorPatientController::class, 'index']);
        Route::get('patients/{patient}', [DoctorPatientController::class, 'show']);
    });

    // ─── Assistant ───────────────────────────────────────────────────────────────
    Route::middleware(['auth:api', 'throttle:api', 'role:assistant', 'clinic.scope'])->prefix('assistant')->group(function () {
        Route::apiResource('patients', AssistantPatientController::class)->except(['destroy']);

        Route::get('appointments', [AssistantAppointmentController::class, 'index']);
        Route::post('appointments', [AssistantAppointmentController::class, 'store']);
        Route::get('appointments/{appointment}', [AssistantAppointmentController::class, 'show']);
        Route::put('appointments/{appointment}', [AssistantAppointmentController::class, 'update']);
        Route::delete('appointments/{appointment}', [AssistantAppointmentController::class, 'destroy']);

        Route::get('available-slots', [AssistantAppointmentController::class, 'availableSlots']);
    });
});
