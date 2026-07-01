<?php

namespace App\Repositories;

use App\Models\WhatsappMessage;
use Illuminate\Pagination\LengthAwarePaginator;

class WhatsappRepository
{
    public function create(array $data): WhatsappMessage
    {
        return WhatsappMessage::create($data);
    }

    public function markSent(WhatsappMessage $msg, string $metaMessageId): WhatsappMessage
    {
        $msg->update([
            'status'          => 'sent',
            'meta_message_id' => $metaMessageId,
        ]);

        return $msg->fresh();
    }

    public function markFailed(WhatsappMessage $msg, string $error): WhatsappMessage
    {
        $msg->update([
            'status'        => 'failed',
            'error_message' => $error,
        ]);

        return $msg->fresh();
    }

    public function paginateForUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WhatsappMessage::select(['id', 'sent_by', 'to_phone', 'message', 'status', 'meta_message_id', 'error_message', 'created_at'])
            ->where('sent_by', $userId)
            ->latest()
            ->paginate($perPage);
    }
}
