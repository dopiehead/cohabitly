<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class NotificationsController extends AbstractController
{
    #[Route('/notification', name: 'app_notification')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(
        UserNotificationRepository $notificationRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $notificationRepo->findUnreadByRecipient($user->getId());
        $notificationCount = $notificationRepo->countUnread($user->getId());

        return $this->render('dashboard/notifications.html.twig', [
            'user' => $user,
            'title' => 'Notifications',
            'notifications' => $notifications,
            'notificationCount' => $notificationCount,
        ]);
    }

    #[Route('/ajax/delete', name: 'app_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(
        UserNotificationRepository $notificationRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $notificationRepo->findAllByRecipient($user->getId());
        $deleteNotification = $notificationRepo->delete($user->getId());

        return $this->render('dashboard/notifications.html.twig', [
            'user' => $user,
            'title' => 'Notifications',
            'notifications' => $notifications,
            'notificationCount' => $notificationCount,
        ]);
    }
}
