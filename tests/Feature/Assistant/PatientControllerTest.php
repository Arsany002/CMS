<?php

namespace Tests\Feature\Assistant;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Tests\ApiTestCase;

class PatientControllerTest extends ApiTestCase
{
    private User   $assistant;
    private Clinic $clinic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clinic    = Clinic::factory()->create();
        $this->assistant = User::factory()->assistant()->forClinic($this->clinic)->create();
        $this->actingAsPassport($this->assistant);
    }

    private function validPatientPayload(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Jane Doe',
            'phone'         => '01000000001',
            'email'         => 'jane.doe@test.test',
            'date_of_birth' => '1990-05-15',
            'gender'        => 'female',
            'address'       => '10 Test Street',
        ], $overrides);
    }

    // ─── GET /api/v1/assistant/patients ─────────────────────────────────────

    public function test_assistant_can_list_patients_in_their_clinic(): void
    {
        Patient::factory()->count(3)->create(['clinic_id' => $this->clinic->id]);

        $response = $this->getJson('/api/v1/assistant/patients');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_patient_list_excludes_patients_from_other_clinics(): void
    {
        $otherClinic = Clinic::factory()->create();
        Patient::factory()->count(2)->create(['clinic_id' => $this->clinic->id]);
        Patient::factory()->count(5)->create(['clinic_id' => $otherClinic->id]);

        $data = $this->getJson('/api/v1/assistant/patients')->json('data');

        $this->assertCount(2, $data);
    }

    // ─── POST /api/v1/assistant/patients ────────────────────────────────────

    public function test_assistant_can_register_a_new_patient(): void
    {
        $response = $this->postJson('/api/v1/assistant/patients', $this->validPatientPayload());

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Patient created'])
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.gender', 'female');

        $this->assertDatabaseHas('patients', [
            'email'     => 'jane.doe@test.test',
            'clinic_id' => $this->clinic->id,
        ]);
    }

    public function test_new_patient_is_automatically_scoped_to_assistants_clinic(): void
    {
        $this->postJson('/api/v1/assistant/patients', $this->validPatientPayload([
            'email' => 'scoped@test.test',
        ]));

        $this->assertDatabaseHas('patients', [
            'email'     => 'scoped@test.test',
            'clinic_id' => $this->clinic->id,
        ]);
    }

    // ─── GET /api/v1/assistant/patients/{id} ────────────────────────────────

    public function test_assistant_can_view_a_patient_in_their_clinic(): void
    {
        $patient = Patient::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name'      => 'View Patient',
        ]);

        $response = $this->getJson("/api/v1/assistant/patients/{$patient->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $patient->id)
            ->assertJsonPath('data.name', 'View Patient');
    }

    public function test_assistant_receives_403_when_viewing_patient_from_another_clinic(): void
    {
        $otherClinic = Clinic::factory()->create();
        $patient     = Patient::factory()->create(['clinic_id' => $otherClinic->id]);

        $response = $this->getJson("/api/v1/assistant/patients/{$patient->id}");

        $response->assertStatus(403);
    }

    // ─── PUT /api/v1/assistant/patients/{id} ────────────────────────────────

    public function test_assistant_can_update_a_patient_in_their_clinic(): void
    {
        $patient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        $response = $this->putJson("/api/v1/assistant/patients/{$patient->id}", [
            'name'   => 'Updated Name',
            'gender' => $patient->gender->value,
            'phone'  => $patient->phone,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Patient updated'])
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('patients', [
            'id'   => $patient->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_assistant_receives_403_when_updating_patient_from_another_clinic(): void
    {
        $otherClinic = Clinic::factory()->create();
        $patient     = Patient::factory()->create(['clinic_id' => $otherClinic->id]);

        $response = $this->putJson("/api/v1/assistant/patients/{$patient->id}", [
            'name'   => 'Hacked Name',
            'gender' => 'male',
        ]);

        $response->assertStatus(403);
    }
}
