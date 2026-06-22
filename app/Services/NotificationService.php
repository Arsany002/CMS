<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function paginate(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginateNotifications($user, $perPage);
    }

    public function unreadCount(User $user): int
    {
        return $this->userRepository->unreadNotificationCount($user);
    }

    public function markAsRead(User $user, string $id): ?DatabaseNotification
    {
        return $this->userRepository->markNotificationAsRead($user, $id);
    }

    public function markAllAsRead(User $user): void
    {
        $this->userRepository->markAllNotificationsAsRead($user);
    }
}
