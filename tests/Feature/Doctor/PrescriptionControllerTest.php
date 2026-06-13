<?php

namespace Tests\Feature\Doctor;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Tests\ApiTestCase;

class PrescriptionControllerTest extends ApiTestCase
{
    private User        $doctor;
    private User        $assistant;
    private Clinic      $clinic;
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
            'clinic_id'  => $this->clinic->id,
            'doctor_id'  => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'booked_by'  => $this->assistant->id,
            'status'     => AppointmentStatus::CONFIRMED,
        ]);

        $this->actingAsPassport($this->doctor);
    }

    private function validPrescriptionPayload(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'diagnosis'      => 'Seasonal flu',
            'notes'          => 'Rest and fluids',
            'items'          => [
                [
                    'medicine_name' => 'Paracetamol 500mg',
                    'dosage'        => '1 tablet',
                    'frequency'     => 'Three times daily',
                    'duration'      => '5 days',
                    'notes'         => 'After meals',
                ],
            ],
        ];
    }

    // ─── GET /api/v1/doctor/prescriptions ───────────────────────────────────

    public function test_doctor_can_list_own_prescriptions(): void
    {
        $prescription = Prescription::factory()->create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'clinic_id'      => $this->clinic->id,
        ]);
        PrescriptionItem::factory()->create(['prescription_id' => $prescription->id]);

        $response = $this->getJson('/api/v1/doctor/prescriptions');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    // ─── POST /api/v1/doctor/prescriptions ──────────────────────────────────

    public function test_doctor_can_create_a_prescription(): void
    {
        $response = $this->postJson('/api/v1/doctor/prescriptions', $this->validPrescriptionPayload());

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Prescription created successfully'])
            ->assertJsonStructure([
                'data' => ['id', 'diagnosis'],
            ]);

        $this->assertDatabaseHas('prescriptions', [
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'diagnosis'      => 'Seasonal flu',
        ]);

        $this->assertDatabaseHas('prescription_items', [
            'medicine_name' => 'Paracetamol 500mg',
        ]);
    }

    public function test_prescription_can_have_multiple_items(): void
    {
        $payload           = $this->validPrescriptionPayload();
        $payload['items'][] = [
            'medicine_name' => 'Ibuprofen 400mg',
            'dosage'        => '1 tablet',
            'frequency'     => 'Twice daily',
            'duration'      => '3 days',
        ];

        $this->postJson('/api/v1/doctor/prescriptions', $payload)->assertStatus(201);

        $this->assertEquals(2, PrescriptionItem::count());
    }

    // ─── GET /api/v1/doctor/prescriptions/{id} ──────────────────────────────

    public function test_doctor_can_view_own_prescription(): void
    {
        $prescription = Prescription::factory()->create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'clinic_id'      => $this->clinic->id,
        ]);
        PrescriptionItem::factory()->create(['prescription_id' => $prescription->id]);

        $response = $this->getJson("/api/v1/doctor/prescriptions/{$prescription->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $prescription->id)
            ->assertJsonStructure([
                'data' => ['id', 'diagnosis'],
            ]);
    }

    // ─── PUT /api/v1/doctor/prescriptions/{id} ──────────────────────────────

    public function test_doctor_can_update_own_prescription(): void
    {
        $prescription = Prescription::factory()->create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'clinic_id'      => $this->clinic->id,
            'diagnosis'      => 'Original diagnosis',
        ]);
        PrescriptionItem::factory()->create(['prescription_id' => $prescription->id]);

        $response = $this->putJson("/api/v1/doctor/prescriptions/{$prescription->id}", [
            'diagnosis' => 'Updated diagnosis',
            'notes'     => 'Updated notes',
            'items'     => [
                [
                    'medicine_name' => 'Amoxicillin 250mg',
                    'dosage'        => '1 capsule',
                    'frequency'     => 'Twice daily',
                    'duration'      => '7 days',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Prescription updated successfully'])
            ->assertJsonPath('data.diagnosis', 'Updated diagnosis');
    }
}
