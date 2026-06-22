<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->service->paginate($request->user());

        return $this->success(data: $notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->service->unreadCount($request->user());

        return $this->success(data: ['count' => $count]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->service->markAsRead($request->user(), $id);

        if (! $notification) {
            return $this->error(message: 'Notification not found', status: 404);
        }

        return $this->success(message: 'Notification marked as read', data: $notification);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->service->markAllAsRead($request->user());

        return $this->success(message: 'All notifications marked as read');
    }
}
