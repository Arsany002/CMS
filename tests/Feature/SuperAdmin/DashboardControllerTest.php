<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Tests\ApiTestCase;

class DashboardControllerTest extends ApiTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAsPassport($this->admin);
    }

    // ─── GET /api/v1/super-admin/dashboard ──────────────────────────────────

    public function test_super_admin_can_view_dashboard_statistics(): void
    {
        $clinic = Clinic::factory()->create();
        User::factory()->doctor()->forClinic($clinic)->count(2)->create();
        Patient::factory()->count(5)->create(['clinic_id' => $clinic->id]);

        $response = $this->getJson('/api/v1/super-admin/dashboard');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Dashboard statistics'])
            ->assertJsonStructure([
                'data' => [
                    'total_clinics',
                    'total_users',
                    'total_patients',
                    'total_appointments',
                    'total_prescriptions',
                    'appointments_today',
                ],
            ]);
    }

    public function test_dashboard_counts_reflect_actual_records(): void
    {
        Clinic::factory()->count(2)->create();

        $data = $this->getJson('/api/v1/super-admin/dashboard')->json('data');

        // The admin itself is 1 user; 2 clinics created above
        $this->assertEquals(2, $data['total_clinics']);
        $this->assertGreaterThanOrEqual(1, $data['total_users']);
    }

    // ─── GET /api/v1/super-admin/appointments ───────────────────────────────

    public function test_super_admin_can_list_all_appointments(): void
    {
        $clinic  = Clinic::factory()->create();
        $doctor  = User::factory()->doctor()->forClinic($clinic)->create();
        $asst    = User::factory()->assistant()->forClinic($clinic)->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $date    = now()->next('Monday')->format('Y-m-d');

        // Use distinct start times to avoid the doctor/date/start_time unique index.
        foreach (['09:00', '10:00', '11:00'] as $start) {
            Appointment::factory()->create([
                'clinic_id'        => $clinic->id,
                'doctor_id'        => $doctor->id,
                'patient_id'       => $patient->id,
                'booked_by'        => $asst->id,
                'appointment_date' => $date,
                'start_time'       => $start,
                'end_time'         => substr($start, 0, 2) + 1 . ':00',
            ]);
        }

        $response = $this->getJson('/api/v1/super-admin/appointments');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    public function test_appointments_can_be_filtered_by_clinic(): void
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();
        $doctorA = User::factory()->doctor()->forClinic($clinicA)->create();
        $doctorB = User::factory()->doctor()->forClinic($clinicB)->create();
        $asstA   = User::factory()->assistant()->forClinic($clinicA)->create();
        $asstB   = User::factory()->assistant()->forClinic($clinicB)->create();
        $patA    = Patient::factory()->create(['clinic_id' => $clinicA->id]);
        $patB    = Patient::factory()->create(['clinic_id' => $clinicB->id]);
        $date    = now()->next('Monday')->format('Y-m-d');

        Appointment::factory()->create([
            'clinic_id' => $clinicA->id, 'doctor_id' => $doctorA->id,
            'patient_id' => $patA->id,  'booked_by'  => $asstA->id,
            'appointment_date' => $date, 'start_time' => '09:00', 'end_time' => '10:00',
        ]);
        Appointment::factory()->create([
            'clinic_id' => $clinicB->id, 'doctor_id' => $doctorB->id,
            'patient_id' => $patB->id,  'booked_by'  => $asstB->id,
            'appointment_date' => $date, 'start_time' => '09:00', 'end_time' => '10:00',
        ]);

        $response = $this->getJson("/api/v1/super-admin/appointments?clinic_id={$clinicA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // ─── GET /api/v1/super-admin/prescriptions ──────────────────────────────

    public function test_super_admin_can_list_all_prescriptions(): void
    {
        $clinic  = Clinic::factory()->create();
        $doctor  = User::factory()->doctor()->forClinic($clinic)->create();
        $asst    = User::factory()->assistant()->forClinic($clinic)->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $appt    = Appointment::factory()->create([
            'clinic_id' => $clinic->id, 'doctor_id' => $doctor->id,
            'patient_id' => $patient->id, 'booked_by' => $asst->id,
        ]);

        $prescription = Prescription::factory()->create([
            'appointment_id' => $appt->id,
            'doctor_id'      => $doctor->id,
            'patient_id'     => $patient->id,
            'clinic_id'      => $clinic->id,
        ]);
        PrescriptionItem::factory()->create(['prescription_id' => $prescription->id]);

        $response = $this->getJson('/api/v1/super-admin/prescriptions');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }
}
