<?php

namespace Tests\Feature\Assistant;

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\ApiTestCase;

class AppointmentControllerTest extends ApiTestCase
{
    private User      $assistant;
    private User      $doctor;
    private Clinic    $clinic;
    private Patient   $patient;
    private Carbon    $nextMonday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic    = Clinic::factory()->create();
        $this->doctor    = User::factory()->doctor()->forClinic($this->clinic)->create();
        $this->assistant = User::factory()->assistant()->forClinic($this->clinic)->create();
        $this->patient   = Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        // Create a Monday schedule so the appointment service can compute slots
        $this->nextMonday = now()->next('Monday');
        DoctorSchedule::factory()->create([
            'doctor_id'     => $this->doctor->id,
            'clinic_id'     => $this->clinic->id,
            'day_of_week'   => 1, // Monday
            'start_time'    => '08:00',
            'end_time'      => '17:00',
            'slot_duration' => 60,
        ]);

        $this->actingAsPassport($this->assistant);
    }

    // ─── GET /api/v1/assistant/appointments ─────────────────────────────────

    public function test_assistant_can_list_appointments_for_their_clinic(): void
    {
        Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time'       => '09:00',
            'end_time'         => '10:00',
        ]);

        $response = $this->getJson('/api/v1/assistant/appointments');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    public function test_appointment_list_is_scoped_to_the_assistants_clinic(): void
    {
        $otherClinic = Clinic::factory()->create();
        $otherDoc    = User::factory()->doctor()->forClinic($otherClinic)->create();
        $otherAsst   = User::factory()->assistant()->forClinic($otherClinic)->create();
        $otherPat    = Patient::factory()->create(['clinic_id' => $otherClinic->id]);

        // Appointment in OUR clinic for today
        Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time'       => '09:00',
            'end_time'         => '10:00',
        ]);
        // Appointment in another clinic — should be excluded
        Appointment::factory()->create([
            'clinic_id'        => $otherClinic->id,
            'doctor_id'        => $otherDoc->id,
            'patient_id'       => $otherPat->id,
            'booked_by'        => $otherAsst->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time'       => '09:00',
            'end_time'         => '10:00',
        ]);

        $this->assertCount(1, $this->getJson('/api/v1/assistant/appointments')->json('data'));
    }

    // ─── POST /api/v1/assistant/appointments ────────────────────────────────

    public function test_assistant_can_book_a_new_appointment(): void
    {
        $response = $this->postJson('/api/v1/assistant/appointments', [
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00', // first slot in the schedule
            'notes'            => 'First appointment',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Appointment booked successfully'])
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('appointments', [
            'doctor_id'  => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status'     => 'pending',
        ]);
    }

    public function test_booked_appointment_end_time_is_auto_computed(): void
    {
        $response = $this->postJson('/api/v1/assistant/appointments', [
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00',
        ]);

        $response->assertStatus(201);
        // slot_duration = 60 min; 08:00 + 60 min = 09:00
        $this->assertStringStartsWith('09:00', $response->json('data.end_time'));
    }

    // ─── GET /api/v1/assistant/appointments/{id} ────────────────────────────

    public function test_assistant_can_view_a_specific_appointment(): void
    {
        $appt = Appointment::factory()->create([
            'clinic_id'  => $this->clinic->id,
            'doctor_id'  => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'booked_by'  => $this->assistant->id,
        ]);

        $response = $this->getJson("/api/v1/assistant/appointments/{$appt->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $appt->id);
    }

    // ─── PUT /api/v1/assistant/appointments/{id} ────────────────────────────

    public function test_assistant_can_reschedule_an_appointment(): void
    {
        // Existing appointment at 08:00; reschedule to 09:00 (next available slot)
        $appt = Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'status'           => AppointmentStatus::PENDING,
        ]);

        $response = $this->putJson("/api/v1/assistant/appointments/{$appt->id}", [
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '09:00', // 08:00 is booked; 09:00 is free
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Appointment rescheduled successfully']);

        // start_time is returned as the PHP in-memory value ('09:00'), not re-fetched from MySQL.
        $this->assertStringStartsWith('09:00', $response->json('data.start_time'));
    }

    // ─── DELETE /api/v1/assistant/appointments/{id} ─────────────────────────

    public function test_assistant_can_cancel_an_appointment(): void
    {
        $appt = Appointment::factory()->create([
            'clinic_id'  => $this->clinic->id,
            'doctor_id'  => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'booked_by'  => $this->assistant->id,
            'status'     => AppointmentStatus::PENDING,
        ]);

        $response = $this->deleteJson("/api/v1/assistant/appointments/{$appt->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Appointment cancelled successfully']);

        $this->assertDatabaseHas('appointments', [
            'id'     => $appt->id,
            'status' => 'cancelled',
        ]);
    }

    // ─── GET /api/v1/assistant/available-slots ───────────────────────────────

    public function test_assistant_can_view_available_slots_for_a_doctor(): void
    {
        $response = $this->getJson(
            '/api/v1/assistant/available-slots'
            . "?doctor_id={$this->doctor->id}"
            . "&date={$this->nextMonday->format('Y-m-d')}"
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['slots']]);

        // 08:00–17:00 with 60-min slots = 9 slots
        $this->assertCount(9, $response->json('data.slots'));
    }

    public function test_available_slots_excludes_already_booked_times(): void
    {
        // Book the 08:00 slot
        Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'status'           => AppointmentStatus::PENDING,
        ]);

        $slots = $this->getJson(
            '/api/v1/assistant/available-slots'
            . "?doctor_id={$this->doctor->id}"
            . "&date={$this->nextMonday->format('Y-m-d')}"
        )->json('data.slots');

        $this->assertNotContains('08:00', $slots);
        // 9 total − 1 booked = 8 remaining
        $this->assertCount(8, $slots);
    }

    // ─── POST /api/v1/assistant/appointments — conflict ─────────────────────

    public function test_double_booking_returns_409_conflict(): void
    {
        $date    = $this->nextMonday->format('Y-m-d');
        $payload = [
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'appointment_date' => $date,
            'start_time'       => '08:00',
        ];

        // Arrange / Act (first): first booking succeeds and the slot is consumed
        $this->postJson('/api/v1/assistant/appointments', $payload)
            ->assertStatus(201);

        // Act (second): identical slot is no longer in available slots — conflict
        $response = $this->postJson('/api/v1/assistant/appointments', $payload);

        // Assert: the service throws AppointmentConflictException → 409
        $response->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_available_slots_returns_empty_when_no_schedule_exists(): void
    {
        $doctor = User::factory()->doctor()->forClinic($this->clinic)->create();
        // No schedule created for this doctor

        $slots = $this->getJson(
            '/api/v1/assistant/available-slots'
            . "?doctor_id={$doctor->id}"
            . "&date={$this->nextMonday->format('Y-m-d')}"
        )->json('data.slots');

        $this->assertEmpty($slots);
    }

    // ─── Invalid-state guard ────────────────────────────────────────────────

    public function test_cannot_reschedule_a_completed_appointment(): void
    {
        $appt = Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'status'           => AppointmentStatus::COMPLETED,
        ]);

        $response = $this->putJson("/api/v1/assistant/appointments/{$appt->id}", [
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '09:00',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_rescheduling_to_an_unavailable_slot_returns_409(): void
    {
        // 08:00 is already booked; try to reschedule another appointment to it
        Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'status'           => AppointmentStatus::PENDING,
        ]);

        $appt = Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '09:00',
            'end_time'         => '10:00',
            'status'           => AppointmentStatus::PENDING,
        ]);

        $response = $this->putJson("/api/v1/assistant/appointments/{$appt->id}", [
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00', // already taken
        ]);

        $response->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    // ─── Queue dispatching ──────────────────────────────────────────────────

    public function test_booking_dispatches_confirmation_job(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/assistant/appointments', [
            'doctor_id'        => $this->doctor->id,
            'patient_id'       => $this->patient->id,
            'appointment_date' => $this->nextMonday->format('Y-m-d'),
            'start_time'       => '08:00',
        ])->assertStatus(201);

        Queue::assertPushed(SendAppointmentConfirmation::class, function ($job) {
            return $job->appointment->doctor_id === $this->doctor->id;
        });
    }
}
