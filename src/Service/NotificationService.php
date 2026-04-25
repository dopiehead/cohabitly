<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Repository\UserNotificationRepository;

class NotificationService
{
    public function __construct(
        private UserNotificationRepository $notificationRepository
    ) {}

    public function notify(User $user, string $type, array $payload): UserNotification
    {
        $notification = new UserNotification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setPayload($payload);

        $this->notificationRepository->save($notification);

        return $notification;
    }
}
