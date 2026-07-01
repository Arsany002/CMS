<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWhatsappMessageRequest;
use App\Services\WhatsappService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class WhatsappController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WhatsappService $service,
    ) {}

    public function send(SendWhatsappMessageRequest $request): JsonResponse
    {
        $message = $this->service->send(
            userId:  $request->user()->id,
            toPhone: $request->validated('to_phone'),
            message: $request->validated('message'),
        );

        return $this->success(data: $message, message: 'WhatsApp message sent successfully.', status: 201);
    }
}
