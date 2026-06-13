<?php

namespace Tests\Feature\Doctor;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Tests\ApiTestCase;

class AppointmentControllerTest extends ApiTestCase
{
    private User      $doctor;
    private User      $assistant;
    private Clinic    $clinic;
    private Patient   $patient;
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
            'appointment_date' => now()->next('Monday')->format('Y-m-d'),
            'start_time'       => '09:00',
            'end_time'         => '10:00',
            'status'           => AppointmentStatus::PENDING,
        ]);

        $this->actingAsPassport($this->doctor);
    }

    // ─── GET /api/v1/doctor/appointments ────────────────────────────────────

    public function test_doctor_can_list_own_appointments(): void
    {
        $response = $this->getJson('/api/v1/doctor/appointments');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    public function test_appointment_list_scoped_to_the_authenticated_doctor(): void
    {
        $otherDoctor = User::factory()->doctor()->forClinic($this->clinic)->create();
        Appointment::factory()->create([
            'clinic_id'        => $this->clinic->id,
            'doctor_id'        => $otherDoctor->id,
            'patient_id'       => $this->patient->id,
            'booked_by'        => $this->assistant->id,
            'appointment_date' => now()->next('Monday')->format('Y-m-d'),
            'start_time'       => '10:00',
            'end_time'         => '11:00',
        ]);

        // Only the authenticated doctor's own appointment should appear
        $this->assertCount(1, $this->getJson('/api/v1/doctor/appointments')->json('data'));
    }

    public function test_doctor_can_filter_appointments_by_date(): void
    {
        $date     = now()->next('Monday')->format('Y-m-d');
        $response = $this->getJson("/api/v1/doctor/appointments?date={$date}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_doctor_can_filter_appointments_by_status(): void
    {
        $response = $this->getJson('/api/v1/doctor/appointments?status=pending');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // ─── GET /api/v1/doctor/appointments/{id} ───────────────────────────────

    public function test_doctor_can_view_a_specific_appointment(): void
    {
        $response = $this->getJson("/api/v1/doctor/appointments/{$this->appointment->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $this->appointment->id)
            ->assertJsonStructure([
                'data' => ['id', 'appointment_date', 'start_time', 'end_time', 'status', 'patient'],
            ]);
    }

    // ─── PATCH /api/v1/doctor/appointments/{id}/status ──────────────────────

    public function test_doctor_can_confirm_a_pending_appointment(): void
    {
        $response = $this->patchJson(
            "/api/v1/doctor/appointments/{$this->appointment->id}/status",
            ['status' => 'confirmed']
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Status updated'])
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'id'     => $this->appointment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_doctor_can_mark_an_appointment_as_completed(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::CONFIRMED]);

        $response = $this->patchJson(
            "/api/v1/doctor/appointments/{$this->appointment->id}/status",
            ['status' => 'completed']
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }
}
