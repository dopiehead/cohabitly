<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\MercureJwtGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ChatController extends AbstractController
{
    #[Route('/chat/{slug}', name: 'app_chat', methods: ['GET'])]
    public function chat(
        MercureJwtGenerator $jwtGenerator,
        string $slug,
        MessageRepository $messageRepository
    ): Response {
        $user = $this->getUser();
        $jwt = $jwtGenerator->generate(
            subscribe: ["chat/{$user->getEmail()}", "chat/{$slug}"],
            publish: ["chat/{$slug}"]
        );
    
        $messages = $messageRepository->getConversation($user->getEmail(), $slug);
        $messageRepository->markAsRead($user->getEmail(), $slug);
    
        return json_encode([
            'title'        => 'Chat',
            'messages'     => $messages,
            'sender'       => $user->getEmail(),
            'receiver'     => $slug,
            'receiverName' => explode('@', $slug)[0],
            'subject'      => 'Direct Message',
            'onlineStatus' => false,
            'mercureJwt'   => $jwt,
        ]);
    }

    #[Route('/ajax/send', name: 'app_send', methods: ['POST'])]
    public function send(
        Request $request,
        MessageRepository $messageRepository,
        MercureJwtGenerator $jwtGenerator,
        HubInterface $hub
    ): Response {
        $user = $this->getUser();
        $receiverEmail = $request->request->get('sentto');
        $content = $request->request->get('message');

        // Save message in DB
        $messageRepository->saveMessage($user->getEmail(), $receiverEmail, 'Direct Message', $content);

        // Generate Mercure JWT for publishing
        $jwt = $jwtGenerator->generate([], ["chat/{$receiverEmail}"]);

        // Publish message to Mercure hub
        $update = new Update(
            'chat/'.$receiverEmail,
            json_encode(['message' => $content, 'sender' => $user->getEmail()]),
            false
        );
        $hub->publish($update);

        return $this->json([
            'status' => 'success',
            'jwt' => $jwt
        ]);
    }
}
