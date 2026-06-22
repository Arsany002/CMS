<?php

namespace Tests\Feature\Notifications;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\DoctorAppointmentReminder;
use Illuminate\Support\Facades\Notification;
use Tests\ApiTestCase;

class AppointmentReminderCommandTest extends ApiTestCase
{
    private Clinic  $clinic;
    private User    $doctor;
    private User    $assistant;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic    = Clinic::factory()->create();
        $this->doctor    = User::factory()->doctor()->forClinic($this->clinic)->create();
        $this->assistant = User::factory()->assistant()->forClinic($this->clinic)->create();
        $this->patient   = Patient::factory()->create(['clinic_id' => $this->clinic->id]);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function makeAppointment(int $minutesFromNow, array $overrides = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => now()->toDateString(),
            'start_time'       => now()->addMinutes($minutesFromNow)->format('H:i:s'),
            'end_time'         => now()->addMinutes($minutesFromNow + 30)->format('H:i:s'),
            'status'           => 'pending',
        ], $overrides));
    }

    // ─── 30-minute email reminders ────────────────────────────────────────────

    public function test_email_reminder_dispatched_for_appointment_within_30_minutes(): void
    {
        Notification::fake();

        $this->makeAppointment(20); // 20 min from now → inside 30-min window

        $this->artisan('appointments:send-doctor-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $this->doctor,
            DoctorAppointmentReminder::class,
            fn ($n) => in_array('mail', $n->via($this->doctor))
        );
    }

    public function test_email_reminded_at_is_set_after_email_reminder_is_dispatched(): void
    {
        Notification::fake();

        $appointment = $this->makeAppointment(20);

        $this->artisan('appointments:send-doctor-reminders');

        $this->assertNotNull($appointment->fresh()->email_reminded_at);
    }

    public function test_email_reminder_not_sent_twice_for_same_appointment(): void
    {
        Notification::fake();

        $this->makeAppointment(20, ['email_reminded_at' => now()]);

        $this->artisan('appointments:send-doctor-reminders');

        Notification::assertNothingSent();
    }

    public function test_email_reminder_not_sent_for_appointment_outside_30_minute_window(): void
    {
        Notification::fake();

        $this->makeAppointment(45); // 45 min from now → outside 30-min window

        $this->artisan('appointments:send-doctor-reminders');

        Notification::assertNothingSent();
    }

    // ─── 15-minute in-app reminders ──────────────────────────────────────────

    public function test_in_app_reminder_dispatched_for_appointment_within_15_minutes(): void
    {
        Notification::fake();

        $this->makeAppointment(10); // 10 min from now → inside 15-min window

        $this->artisan('appointments:send-doctor-reminders');

        Notification::assertSentTo(
            $this->doctor,
            DoctorAppointmentReminder::class,
            fn ($n) => in_array('database', $n->via($this->doctor))
        );
    }

    public function test_app_reminded_at_is_set_after_in_app_reminder_is_dispatched(): void
    {
        Notification::fake();

        $appointment = $this->makeAppointment(10);

        $this->artisan('appointments:send-doctor-reminders');

        $this->assertNotNull($appointment->fresh()->app_reminded_at);
    }

    public function test_in_app_reminder_not_sent_twice_for_same_appointment(): void
    {
        Notification::fake();

        // Both columns set so neither the email nor the in-app reminder fires
        $this->makeAppointment(10, [
            'app_reminded_at'   => now(),
            'email_reminded_at' => now(),
        ]);

        $this->artisan('appointments:send-doctor-reminders');

        Notification::assertNothingSent();
    }

    // ─── Status filtering ─────────────────────────────────────────────────────

    public function test_cancelled_appointment_does_not_trigger_reminder(): void
    {
        Notification::fake();

        $this->makeAppointment(10, ['status' => 'cancelled']);

        $this->artisan('appointments:send-doctor-reminders');

        Notification::assertNothingSent();
    }

    public function test_completed_appointment_does_not_trigger_reminder(): void
    {
        Notification::fake();

        $this->makeAppointment(10, ['status' => 'completed']);

        $this->artisan('appointments:send-doctor-reminders');

        Notification::assertNothingSent();
    }

    public function test_confirmed_appointment_triggers_reminder(): void
    {
        Notification::fake();

        $this->makeAppointment(10, ['status' => 'confirmed']);

        $this->artisan('appointments:send-doctor-reminders');

        Notification::assertSentTo($this->doctor, DoctorAppointmentReminder::class);
    }

    // ─── Edge cases ───────────────────────────────────────────────────────────

    public function test_command_skips_gracefully_when_no_appointments_are_due(): void
    {
        Notification::fake();

        $this->makeAppointment(60); // 60 min from now → outside both windows

        $this->artisan('appointments:send-doctor-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_both_reminders_sent_when_appointment_is_within_15_minutes(): void
    {
        Notification::fake();

        $this->makeAppointment(10); // inside both the 15-min and 30-min windows

        $this->artisan('appointments:send-doctor-reminders');

        // Notification::assertSentToTimes counts all dispatches to the doctor
        Notification::assertSentToTimes($this->doctor, DoctorAppointmentReminder::class, 2);
    }
}
