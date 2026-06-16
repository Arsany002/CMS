<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command;
use Database\Seeders\PassportClientSeeder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cms:local-setup', function (): int {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Refusing to run local setup outside local/testing environments.');

        return Command::FAILURE;
    }

    $this->info('Running migrations...');
    Artisan::call('migrate', ['--force' => true]);
    $this->output->write(Artisan::output());

    $privateKey = storage_path('oauth-private.key');
    $publicKey = storage_path('oauth-public.key');

    if (! file_exists($privateKey) || ! file_exists($publicKey)) {
        $this->info('Generating Passport keys...');
        Artisan::call('passport:keys', ['--no-interaction' => true]);
        $this->output->write(Artisan::output());
    } else {
        $this->info('Passport keys already exist.');
    }

    foreach ([$privateKey, $publicKey] as $keyPath) {
        if (file_exists($keyPath)) {
            @chmod($keyPath, 0600);
        }
    }

    $this->info('Ensuring Passport personal access client exists...');
    Artisan::call('db:seed', [
        '--class' => PassportClientSeeder::class,
        '--force' => true,
    ]);
    $this->output->write(Artisan::output());

    $this->info('Local CMS setup complete. You can now run: php artisan serve --host=127.0.0.1 --port=8001');

    return Command::SUCCESS;
})->purpose('Prepare local/testing CMS auth dependencies for manual development');

Artisan::command('cms:cleanup-test-data', function (): int {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Refusing to cleanup test data outside local/testing environments.');

        return Command::FAILURE;
    }

    $playwrightClinicIds = DB::table('clinics')
        ->where('name', 'like', 'Playwright%')
        ->orWhere('email', 'like', 'playwright_%')
        ->pluck('id');

    $playwrightUserIds = DB::table('users')
        ->where('email', 'like', 'playwright_%')
        ->pluck('id');

    $playwrightPatientIds = DB::table('patients')
        ->where('name', 'like', 'Playwright%')
        ->orWhere('email', 'like', 'playwright_%')
        ->pluck('id');

    $appointmentQuery = DB::table('appointments')
        ->whereIn('clinic_id', $playwrightClinicIds)
        ->orWhereIn('doctor_id', $playwrightUserIds)
        ->orWhereIn('booked_by', $playwrightUserIds)
        ->orWhereIn('patient_id', $playwrightPatientIds)
        ->orWhere('notes', 'like', 'Playwright%');

    $playwrightAppointmentIds = $appointmentQuery->pluck('id');

    $playwrightPrescriptionIds = DB::table('prescriptions')
        ->whereIn('clinic_id', $playwrightClinicIds)
        ->orWhereIn('doctor_id', $playwrightUserIds)
        ->orWhereIn('patient_id', $playwrightPatientIds)
        ->orWhereIn('appointment_id', $playwrightAppointmentIds)
        ->orWhere('diagnosis', 'like', 'Playwright%')
        ->orWhere('notes', 'like', 'Playwright%')
        ->pluck('id');

    $playwrightAccessTokenIds = DB::table('oauth_access_tokens')
        ->whereIn('user_id', $playwrightUserIds)
        ->pluck('id');

    $counts = DB::transaction(function () use (
        $playwrightClinicIds,
        $playwrightUserIds,
        $playwrightPatientIds,
        $playwrightAppointmentIds,
        $playwrightPrescriptionIds,
        $playwrightAccessTokenIds
    ): array {
        $prescriptionItems = $playwrightPrescriptionIds->isEmpty()
            ? 0
            : DB::table('prescription_items')
                ->whereIn('prescription_id', $playwrightPrescriptionIds)
                ->delete();

        $prescriptions = $playwrightPrescriptionIds->isEmpty()
            ? 0
            : DB::table('prescriptions')
                ->whereIn('id', $playwrightPrescriptionIds)
                ->delete();

        $appointments = $playwrightAppointmentIds->isEmpty()
            ? 0
            : DB::table('appointments')
                ->whereIn('id', $playwrightAppointmentIds)
                ->delete();

        $patients = $playwrightPatientIds->isEmpty()
            ? 0
            : DB::table('patients')
                ->whereIn('id', $playwrightPatientIds)
                ->delete();

        $schedules = $playwrightUserIds->isEmpty()
            ? 0
            : DB::table('doctor_schedules')
                ->whereIn('doctor_id', $playwrightUserIds)
                ->delete();

        $refreshTokens = $playwrightAccessTokenIds->isEmpty()
            ? 0
            : DB::table('oauth_refresh_tokens')
                ->whereIn('access_token_id', $playwrightAccessTokenIds)
                ->delete();

        $tokens = $playwrightUserIds->isEmpty()
            ? 0
            : DB::table('oauth_access_tokens')
                ->whereIn('user_id', $playwrightUserIds)
                ->delete();

        $users = $playwrightUserIds->isEmpty()
            ? 0
            : DB::table('users')
                ->whereIn('id', $playwrightUserIds)
                ->where('email', 'like', 'playwright_%')
                ->delete();

        $clinics = $playwrightClinicIds->isEmpty()
            ? 0
            : DB::table('clinics')
                ->whereIn('id', $playwrightClinicIds)
                ->where(function ($query): void {
                    $query->where('name', 'like', 'Playwright%')
                        ->orWhere('email', 'like', 'playwright_%');
                })
                ->delete();

        return compact(
            'prescriptionItems',
            'prescriptions',
            'appointments',
            'patients',
            'schedules',
            'refreshTokens',
            'tokens',
            'users',
            'clinics'
        );
    });

    foreach ($counts as $label => $count) {
        $this->line(sprintf('%s: %d', $label, $count));
    }

    $this->info('Playwright test data cleanup complete.');

    return Command::SUCCESS;
})->purpose('Safely remove Playwright-owned test data in local/testing only');
