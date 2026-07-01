<?php

namespace App\Services;

use App\Exceptions\WhatsappSendException;
use App\Models\WhatsappMessage;
use App\Repositories\WhatsappRepository;
use Illuminate\Support\Facades\Http;

class WhatsappService
{
    public function __construct(
        private readonly WhatsappRepository $repository,
    ) {}

    public function send(string $userId, string $toPhone, string $message): WhatsappMessage
    {
        $record = $this->repository->create([
            'sent_by'  => $userId,
            'to_phone' => $this->normalizePhone($toPhone),
            'message'  => $message,
            'status'   => 'pending',
        ]);

        $version       = config('services.whatsapp.api_version');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $token         = config('services.whatsapp.token');

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $record->to_phone,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown error from Meta API.';
            $this->repository->markFailed($record, $error);
            throw new WhatsappSendException($error);
        }

        $metaMessageId = $response->json('messages.0.id') ?? '';

        return $this->repository->markSent($record, $metaMessageId);
    }

   
    public function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }
}
