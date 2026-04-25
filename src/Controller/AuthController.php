<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    // ─────────────────────────────────────────────
    // POST /api/auth/register
    // ─────────────────────────────────────────────
    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        MailService $mailService,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $username = trim($data['username'] ?? '');
        $role     = $data['role'] ?? 'tenant'; // tenant | owner | both

        if (!$email || !$password || !$username) {
            return $this->json(['status' => false, 'message' => 'email, password and username are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['status' => false, 'message' => 'Invalid email format'], 400);
        }

        if ($userRepository->emailExists($email)) {
            return $this->json(['status' => false, 'message' => 'Email already registered'], 409);
        }

        $user = $userRepository->createUser([
            'user_name'     => $username,
            'user_email'    => $email,
            'user_password' => $password,
            'user_phone'    => $data['phone'] ?? null,
            'user_location' => $data['location'] ?? null,
            'lga'           => $data['lga'] ?? null,
            'user_address'  => $data['address'] ?? null,
            'user_gender'   => $data['gender'] ?? null,
        ]);

        $user->setUserRole($role);
        $userRepository->save($user);

        // Send verification email
        $verifyUrl = $urlGenerator->generate(
            'auth_verify_email',
            ['token' => $user->getVkey()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $mailService->sendVerificationEmail($email, $username, $verifyUrl);
        } catch (\Throwable) {
            // non-blocking — log in production
        }

        return $this->json([
            'status'  => true,
            'message' => 'Registration successful. Please verify your email.',
        ], 201);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/verify-email
    // ─────────────────────────────────────────────
    #[Route('/verify-email', name: 'auth_verify_email', methods: ['POST'])]
    public function verifyEmail(
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true) ?? [];
        $token = $data['token'] ?? $request->query->get('token', '');

        if (!$token) {
            return $this->json(['status' => false, 'message' => 'Token is required'], 400);
        }

        $user = $userRepository->findOneBy(['vkey' => $token]);

        if (!$user) {
            return $this->json(['status' => false, 'message' => 'Invalid or expired token'], 400);
        }

        if ($user->isVerified()) {
            return $this->json(['status' => true, 'message' => 'Email already verified']);
        }

        $user->setVerified(true);
        $user->setVkey(null);
        $userRepository->save($user);

        return $this->json(['status' => true, 'message' => 'Email verified successfully']);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/refresh
    // ─────────────────────────────────────────────
    #[Route('/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        UserRepository $userRepository,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data         = json_decode($request->getContent(), true) ?? [];
        $refreshToken = $data['refresh_token'] ?? $request->cookies->get('refresh_token', '');

        if (!$refreshToken) {
            return $this->json(['status' => false, 'message' => 'Refresh token required'], 401);
        }

        $hashed = hash('sha256', $refreshToken);
        $user   = $userRepository->findOneBy(['refreshToken' => $hashed]);

        if (!$user) {
            return $this->json(['status' => false, 'message' => 'Invalid or expired refresh token'], 401);
        }

        // Rotate refresh token
        $newRaw     = bin2hex(random_bytes(32));
        $newHashed  = hash('sha256', $newRaw);
        $user->setRefreshToken($newHashed);
        $userRepository->save($user);

        $accessToken = $jwtManager->create($user);

        return $this->json([
            'status'        => true,
            'access_token'  => $accessToken,
            'refresh_token' => $newRaw,
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/forgot-password
    // ─────────────────────────────────────────────
    #[Route('/forgot-password', name: 'auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        MailService $mailService,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true) ?? [];
        $email = trim($data['email'] ?? '');

        if (!$email) {
            return $this->json(['status' => false, 'message' => 'Email is required'], 400);
        }

        $user = $userRepository->findByEmail($email);

        // Always return 200 to avoid user enumeration
        if ($user) {
            $token     = bin2hex(random_bytes(32));
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($expiresAt);
            $userRepository->save($user);

            $resetUrl = $urlGenerator->generate(
                'auth_reset_password',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) . '?token=' . $token;

            try {
                $mailService->sendResetPasswordEmail($email, $resetUrl);
            } catch (\Throwable) {}
        }

        return $this->json(['status' => true, 'message' => 'If that email exists, a reset link has been sent.']);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/reset-password
    // ─────────────────────────────────────────────
    #[Route('/reset-password', name: 'auth_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true) ?? [];
        $token    = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        if (!$token || !$password) {
            return $this->json(['status' => false, 'message' => 'token and password are required'], 400);
        }

        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['status' => false, 'message' => 'Invalid or expired token'], 400);
        }

        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $userRepository->save($user);

        return $this->json(['status' => true, 'message' => 'Password reset successfully']);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/logout
    // ─────────────────────────────────────────────
    #[Route('/logout', name: 'auth_logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(
        UserRepository $userRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $user->setRefreshToken(null);
        $userRepository->save($user);

        return $this->json(['status' => true, 'message' => 'Logged out successfully']);
    }
}
