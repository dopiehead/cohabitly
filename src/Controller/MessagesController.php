<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MessagesController extends AbstractController
{
    #[Route('/messages', name: 'app_messages')]
    #[IsGranted('ROLE_USER')]
    public function messages(
        Request $request,
        MessageRepository $messageRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $page = $request->query->getInt('page', 1);

        $inbox = $messageRepository->getInbox(
            $user->getEmail(),
            $page
        );

        // First message sender email (if exists)
        $firstMessage = $inbox['messages'][0] ?? null;
        $slug = $firstMessage ? $firstMessage->getSenderEmail() : null;

        return $this->render('dashboard/messages.html.twig', [
            'user'   => $user,
            'title'  => 'Messages',
            'inbox'  => $inbox,
            'slug'   => null,
        ]);
    }
}
