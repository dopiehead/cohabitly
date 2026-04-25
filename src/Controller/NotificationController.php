<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notification')]
#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    // ─────────────────────────────────────────────
    // GET /api/notification  — paginated, unread first
    // ─────────────────────────────────────────────
    #[Route('', name: 'notification_index', methods: ['GET'])]
    public function index(
        Request $request,
        UserNotificationRepository $repo
    ): JsonResponse {
        /** @var User $user */
        $user  = $this->getUser();
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $notifications = $repo->findByUser($user, $page, $limit);
        $unread        = $repo->countUnread($user);

        return $this->json([
            'status'       => true,
            'unread_count' => $unread,
            'data'         => array_map(fn($n) => [
                'id'         => $n->getId(),
                'type'       => $n->getType(),
                'payload'    => $n->getPayload(),
                'read'       => $n->isRead(),
                'created_at' => $n->getCreatedAt()->format(\DateTime::ATOM),
            ], $notifications),
            'meta' => ['page' => $page, 'limit' => $limit],
        ]);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/notification/:id/read
    // ─────────────────────────────────────────────
    #[Route('/{id}/read', name: 'notification_read', methods: ['PATCH'])]
    public function markRead(int $id, UserNotificationRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user         = $this->getUser();
        $notification = $repo->find($id);

        if (!$notification || $notification->getUser()->getId() !== $user->getId()) {
            return $this->json(['status' => false, 'message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();
        $repo->save($notification);

        return $this->json([
            'status' => true,
            'data'   => [
                'id'   => $notification->getId(),
                'read' => $notification->isRead(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/notification/read-all
    // ─────────────────────────────────────────────
    #[Route('/read-all', name: 'notification_read_all', methods: ['PATCH'])]
    public function markAllRead(UserNotificationRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $repo->markAllRead($user);

        return $this->json(['status' => true, 'message' => 'All notifications marked as read']);
    }
}
