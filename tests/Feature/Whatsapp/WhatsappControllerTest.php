<?php

namespace Tests\Feature\Whatsapp;

use App\Models\Clinic;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Tests\ApiTestCase;

class WhatsappControllerTest extends ApiTestCase
{
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $clinic       = Clinic::factory()->create();
        $this->doctor = User::factory()->doctor()->forClinic($clinic)->create();

        $this->actingAsPassport($this->doctor);
    }

    // ─── POST /api/v1/whatsapp/send — success ────────────────────────────────

    public function test_authenticated_user_can_send_whatsapp_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test123']],
            ], 200),
        ]);

        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => '201012345678',
            'message'  => 'Hello from CMS',
        ])
            ->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'WhatsApp message sent successfully.'])
            ->assertJsonPath('data.status', 'sent')
            ->assertJsonPath('data.meta_message_id', 'wamid.test123');
    }

    public function test_whatsapp_message_record_is_persisted_with_sent_status(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.abc']],
            ], 200),
        ]);

        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => '201012345678',
            'message'  => 'Test message',
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'sent_by'         => $this->doctor->id,
            'to_phone'        => '201012345678',
            'status'          => 'sent',
            'meta_message_id' => 'wamid.abc',
        ]);
    }

    // ─── POST /api/v1/whatsapp/send — Meta API failure ───────────────────────

    public function test_meta_api_error_returns_422_and_marks_record_failed(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid phone number'],
            ], 400),
        ]);

        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => '201012345678',
            'message'  => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'sent_by' => $this->doctor->id,
            'status'  => 'failed',
        ]);
    }

    public function test_failed_record_stores_error_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Access token expired'],
            ], 401),
        ]);

        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => '201012345678',
            'message'  => 'Hello',
        ]);

        $record = WhatsappMessage::where('sent_by', $this->doctor->id)->latest()->first();

        $this->assertEquals('failed', $record->status);
        $this->assertEquals('Access token expired', $record->error_message);
    }

    // ─── POST /api/v1/whatsapp/send — validation ─────────────────────────────

    public function test_missing_phone_returns_422(): void
    {
        $this->postJson('/api/v1/whatsapp/send', [
            'message' => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to_phone']);
    }

    public function test_invalid_phone_format_returns_422(): void
    {
        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => 'not-a-phone',
            'message'  => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to_phone']);
    }

    public function test_missing_message_returns_422(): void
    {
        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => '201012345678',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_message_exceeding_4096_chars_returns_422(): void
    {
        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => '201012345678',
            'message'  => str_repeat('a', 4097),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    // ─── Auth ────────────────────────────────────────────────────────────────

    public function test_assistant_can_also_send_whatsapp_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.asst']],
            ], 200),
        ]);

        $clinic    = Clinic::factory()->create();
        $assistant = User::factory()->assistant()->forClinic($clinic)->create();
        $this->actingAsPassport($assistant);

        $this->postJson('/api/v1/whatsapp/send', [
            'to_phone' => '441234567890',
            'message'  => 'Hello from assistant',
        ])
            ->assertStatus(201)
            ->assertJson(['success' => true]);
    }
}
