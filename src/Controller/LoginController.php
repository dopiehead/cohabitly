<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    public function ajaxLogin(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Try JSON first
        $data = json_decode($request->getContent(), true);

        // Fallback to form-data
        $email = trim($data['email'] ?? $request->request->get('email', ''));
        $password = trim($data['password'] ?? $request->request->get('password', ''));

        if (!$email || !$password) {
            return $this->json(['status' => false, 'message' => 'All fields are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['status' => false, 'message' => 'Invalid email'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['user_email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['status' => false, 'message' => 'Invalid credentials'], 401);
        }

        return $this->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getUserName(),
                'email' => $user->getUserEmail(),
            ]
        ]);
    }
}