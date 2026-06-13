<?php

namespace Tests\Feature\Doctor;

use App\Models\Clinic;
use App\Models\DoctorSchedule;
use App\Models\User;
use Tests\ApiTestCase;

class ScheduleControllerTest extends ApiTestCase
{
    private User   $doctor;
    private Clinic $clinic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clinic = Clinic::factory()->create();
        $this->doctor = User::factory()->doctor()->forClinic($this->clinic)->create();
        $this->actingAsPassport($this->doctor);
    }

    // ─── GET /api/v1/doctor/schedules ───────────────────────────────────────

    public function test_doctor_can_list_own_schedules(): void
    {
        DoctorSchedule::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => $this->clinic->id,
        ]);

        $response = $this->getJson('/api/v1/doctor/schedules');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_schedule_list_only_returns_the_doctors_own_schedules(): void
    {
        $otherDoctor = User::factory()->doctor()->forClinic($this->clinic)->create();
        DoctorSchedule::factory()->create(['doctor_id' => $this->doctor->id, 'clinic_id' => $this->clinic->id]);
        DoctorSchedule::factory()->create(['doctor_id' => $otherDoctor->id, 'clinic_id' => $this->clinic->id]);

        $data = $this->getJson('/api/v1/doctor/schedules')->json('data');

        $this->assertCount(1, $data);
    }

    // ─── POST /api/v1/doctor/schedules ──────────────────────────────────────

    public function test_doctor_can_create_a_schedule(): void
    {
        $response = $this->postJson('/api/v1/doctor/schedules', [
            'day_of_week'   => 1,
            'start_time'    => '08:00',
            'end_time'      => '16:00',
            'slot_duration' => 30,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Schedule created successfully'])
            ->assertJsonStructure([
                'data' => ['id', 'day_of_week', 'start_time', 'end_time', 'slot_duration', 'is_active'],
            ]);

        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id'   => $this->doctor->id,
            'clinic_id'   => $this->clinic->id,
            'day_of_week' => 1,
            'is_active'   => 1,
        ]);
    }

    // ─── GET /api/v1/doctor/schedules/{id} ──────────────────────────────────

    public function test_doctor_can_view_own_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => $this->clinic->id,
        ]);

        $response = $this->getJson("/api/v1/doctor/schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $schedule->id);
    }

    // ─── PUT /api/v1/doctor/schedules/{id} ──────────────────────────────────

    public function test_doctor_can_update_own_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'doctor_id'     => $this->doctor->id,
            'clinic_id'     => $this->clinic->id,
            'slot_duration' => 30,
        ]);

        $response = $this->putJson("/api/v1/doctor/schedules/{$schedule->id}", [
            'day_of_week'   => 2,
            'start_time'    => '09:00',
            'end_time'      => '17:00',
            'slot_duration' => 60,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Schedule updated successfully'])
            ->assertJsonPath('data.slot_duration', 60);

        $this->assertDatabaseHas('doctor_schedules', [
            'id'            => $schedule->id,
            'slot_duration' => 60,
        ]);
    }

    // ─── DELETE /api/v1/doctor/schedules/{id} ───────────────────────────────

    public function test_doctor_can_delete_own_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => $this->clinic->id,
        ]);

        $response = $this->deleteJson("/api/v1/doctor/schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Schedule deleted successfully']);

        $this->assertDatabaseMissing('doctor_schedules', ['id' => $schedule->id]);
    }
}
