<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Service\MercureJwtGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/conversation')]
#[IsGranted('ROLE_USER')]
final class ConversationController extends AbstractController
{
    // ─────────────────────────────────────────────
    // GET /api/conversation  — user's conversations
    // ─────────────────────────────────────────────
    #[Route('', name: 'conversation_index', methods: ['GET'])]
    public function index(
        Request $request,
        ConversationRepository $conversationRepository
    ): JsonResponse {
        /** @var User $user */
        $user  = $this->getUser();
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $conversations = $conversationRepository->findByParticipant($user, $page, $limit);

        return $this->json([
            'status' => true,
            'data'   => array_map(fn($c) => [
                'id'           => $c->getId(),
                'listing_id'   => $c->getListing()->getId(),
                'owner_id'     => $c->getOwner()->getId(),
                'applicant_id' => $c->getApplicant()->getId(),
                'created_at'   => $c->getCreatedAt()->format(\DateTime::ATOM),
            ], $conversations),
            'meta' => ['page' => $page, 'limit' => $limit],
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/conversation/:id/messages  — paginated messages
    // ─────────────────────────────────────────────
    #[Route('/{id}/messages', name: 'conversation_messages', methods: ['GET'])]
    public function messages(
        int $id,
        Request $request,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        MercureJwtGenerator $jwtGenerator
    ): JsonResponse {
        /** @var User $user */
        $user         = $this->getUser();
        $conversation = $conversationRepository->find($id);

        if (!$conversation) {
            return $this->json(['status' => false, 'message' => 'Conversation not found'], 404);
        }

        if (!$this->isParticipant($conversation, $user)) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 30);

        $messages = $messageRepository->findByConversation($conversation, $page, $limit);
        $total    = $messageRepository->countByConversation($conversation);

        // Mark messages as read
        $messageRepository->markConversationRead($conversation, $user);

        // Mercure JWT for real-time subscription
        $topic = "conversation/{$id}";
        $jwt   = $jwtGenerator->generate(subscribe: [$topic], publish: [$topic]);

        return $this->json([
            'status'      => true,
            'mercure_jwt' => $jwt,
            'data'        => array_map(fn($m) => [
                'id'         => $m->getId(),
                'sender_id'  => $m->getSender()->getId(),
                'content'    => $m->getContent(),
                'read'       => $m->isRead(),
                'created_at' => $m->getCreatedAt()->format(\DateTime::ATOM),
            ], $messages),
            'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)],
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/conversation/:id/messages  — send message
    // ─────────────────────────────────────────────
    #[Route('/{id}/messages', name: 'conversation_send', methods: ['POST'])]
    public function send(
        int $id,
        Request $request,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        HubInterface $hub,
        MercureJwtGenerator $jwtGenerator
    ): JsonResponse {
        /** @var User $user */
        $user         = $this->getUser();
        $conversation = $conversationRepository->find($id);

        if (!$conversation) {
            return $this->json(['status' => false, 'message' => 'Conversation not found'], 404);
        }

        if (!$this->isParticipant($conversation, $user)) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $data    = json_decode($request->getContent(), true) ?? [];
        $content = trim($data['content'] ?? '');

        if (!$content) {
            return $this->json(['status' => false, 'message' => 'content is required'], 400);
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($content);
        $messageRepository->save($message);

        // Publish via Mercure
        $topic = "conversation/{$id}";
        $hub->publish(new Update(
            $topic,
            json_encode([
                'id'         => $message->getId(),
                'sender_id'  => $user->getId(),
                'content'    => $content,
                'created_at' => $message->getCreatedAt()->format(\DateTime::ATOM),
            ])
        ));

        return $this->json([
            'status' => true,
            'data'   => [
                'id'         => $message->getId(),
                'sender_id'  => $user->getId(),
                'content'    => $message->getContent(),
                'read'       => $message->isRead(),
                'created_at' => $message->getCreatedAt()->format(\DateTime::ATOM),
            ],
        ], 201);
    }

    private function isParticipant($conversation, User $user): bool
    {
        return $conversation->getOwner()->getId() === $user->getId()
            || $conversation->getApplicant()->getId() === $user->getId();
    }
}
