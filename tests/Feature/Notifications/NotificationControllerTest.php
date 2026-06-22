<?php

namespace Tests\Feature\Notifications;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\DoctorAppointmentReminder;
use Tests\ApiTestCase;

class NotificationControllerTest extends ApiTestCase
{
    private Clinic      $clinic;
    private User        $doctor;
    private User        $assistant;
    private Patient     $patient;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic    = Clinic::factory()->create();
        $this->doctor    = User::factory()->doctor()->forClinic($this->clinic)->create();
        $this->assistant = User::factory()->assistant()->forClinic($this->clinic)->create();
        $this->patient   = Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        $this->appointment = Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'status'           => 'pending',
        ]);

        $this->actingAsPassport($this->doctor);
    }

    private function seedNotification(?User $user = null): string
    {
        $target = $user ?? $this->doctor;
        $target->notify(new DoctorAppointmentReminder($this->appointment->id, ['database']));
        return $target->notifications()->latest()->first()->id;
    }

    // ─── GET /api/v1/notifications ────────────────────────────────────────────

    public function test_doctor_can_list_own_notifications(): void
    {
        $this->seedNotification();

        $this->getJson('/api/v1/notifications')
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    public function test_notifications_list_contains_correct_payload_structure(): void
    {
        $this->seedNotification();

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'type', 'data', 'read_at', 'created_at']],
            ]);
    }

    public function test_doctor_only_sees_own_notifications(): void
    {
        $otherDoctor = User::factory()->doctor()->forClinic($this->clinic)->create();
        $this->seedNotification($otherDoctor); // belongs to another doctor
        $this->seedNotification($this->doctor); // belongs to authenticated doctor

        $data = $this->getJson('/api/v1/notifications')->json('data');

        $this->assertCount(1, $data);
    }

    // ─── GET /api/v1/notifications/unread-count ───────────────────────────────

    public function test_unread_count_returns_zero_when_no_notifications(): void
    {
        $this->getJson('/api/v1/notifications/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.count', 0);
    }

    public function test_unread_count_increments_when_notification_is_seeded(): void
    {
        $this->seedNotification();
        $this->seedNotification();

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.count', 2);
    }

    // ─── PATCH /api/v1/notifications/{id}/read ────────────────────────────────

    public function test_doctor_can_mark_a_notification_as_read(): void
    {
        $id = $this->seedNotification();

        $this->patchJson("/api/v1/notifications/{$id}/read")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotNull(
            $this->doctor->notifications()->find($id)?->read_at
        );
    }

    public function test_marking_notification_as_read_decrements_unread_count(): void
    {
        $id = $this->seedNotification();

        $this->patchJson("/api/v1/notifications/{$id}/read");

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.count', 0);
    }

    public function test_marking_another_doctors_notification_returns_404(): void
    {
        $otherDoctor = User::factory()->doctor()->forClinic($this->clinic)->create();
        $otherId     = $this->seedNotification($otherDoctor);

        $this->patchJson("/api/v1/notifications/{$otherId}/read")
            ->assertStatus(404);
    }

    public function test_marking_nonexistent_notification_returns_404(): void
    {
        $this->patchJson('/api/v1/notifications/nonexistent-uuid/read')
            ->assertStatus(404);
    }

    // ─── PATCH /api/v1/notifications/read-all ────────────────────────────────

    public function test_doctor_can_mark_all_notifications_as_read(): void
    {
        $this->seedNotification();
        $this->seedNotification();

        $this->patchJson('/api/v1/notifications/read-all')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.count', 0);
    }

    public function test_mark_all_read_does_not_affect_other_doctors_notifications(): void
    {
        $otherDoctor = User::factory()->doctor()->forClinic($this->clinic)->create();
        $this->seedNotification($otherDoctor);

        $this->patchJson('/api/v1/notifications/read-all');

        // Other doctor's notification must remain unread
        $this->assertNull(
            $otherDoctor->notifications()->latest()->first()?->read_at
        );
    }
}
