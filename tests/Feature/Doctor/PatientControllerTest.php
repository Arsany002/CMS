<?php

namespace Tests\Feature\Doctor;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Tests\ApiTestCase;

class PatientControllerTest extends ApiTestCase
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

    // ─── GET /api/v1/doctor/patients ────────────────────────────────────────

    public function test_doctor_can_list_all_patients_in_their_clinic(): void
    {
        Patient::factory()->count(4)->create(['clinic_id' => $this->clinic->id]);

        $response = $this->getJson('/api/v1/doctor/patients');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(4, 'data');
    }

    public function test_patient_list_is_scoped_to_the_doctors_clinic(): void
    {
        $otherClinic = Clinic::factory()->create();
        Patient::factory()->count(2)->create(['clinic_id' => $this->clinic->id]);
        Patient::factory()->count(3)->create(['clinic_id' => $otherClinic->id]);

        $data = $this->getJson('/api/v1/doctor/patients')->json('data');

        // Only the 2 patients from the doctor's clinic should appear
        $this->assertCount(2, $data);
    }

    public function test_patient_list_returns_correct_fields(): void
    {
        Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        $response = $this->getJson('/api/v1/doctor/patients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'phone', 'gender'],
                ],
            ]);
    }

    // ─── GET /api/v1/doctor/patients/{id} ───────────────────────────────────

    public function test_doctor_can_view_a_patient_in_their_clinic(): void
    {
        $patient = Patient::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name'      => 'Patient Joe',
        ]);

        $response = $this->getJson("/api/v1/doctor/patients/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $patient->id)
            ->assertJsonPath('data.name', 'Patient Joe');
    }

    public function test_doctor_receives_403_when_viewing_patient_from_another_clinic(): void
    {
        $otherClinic = Clinic::factory()->create();
        $patient     = Patient::factory()->create(['clinic_id' => $otherClinic->id]);

        $response = $this->getJson("/api/v1/doctor/patients/{$patient->id}");

        $response->assertStatus(403);
    }
}
