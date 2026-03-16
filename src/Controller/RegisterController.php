<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function apiRegister(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $username = trim($data['username'] ?? '');
        $userEmail = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone    = $data['phone'] ?? '';
        $cpassword = $data['confirmPassword'] ?? '';

        if($password != $cpassword){
            return $this->json([
                'status' => false,
                'message' => 'Password mismatch'
            ], 400);

        }

        if (!$username || !$userEmail || !$password) {
            return $this->json([
                'status' => false,
                'message' => 'All required fields are mandatory'
            ], 400);
        }


        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'status' => false,
                'message' => 'Invalid email format'
            ], 400);
        }

        // Check if email already exists
        if ($userRepository->emailExists($userEmail)) {
            return $this->json([
                'status' => false,
                'message' => 'Email already registered'
            ], 409);
        }

        // Create user via repository
        $user = $userRepository->createUser([
       
            'user_name' => $username,
            '' => $userEmail,
            'user_password' => $password,
            'user_phone' => $data['phone'] ?? null,
            'user_location' => $data['location'] ?? null,
            'lga' => $data['lga'] ?? null,
            'user_address' => $data['address'] ?? null,
            'user_gender' => $data['gender'] ?? null,
        ]);

        // Generate verification URL
        // $verificationUrl = $urlGenerator->generate(
        //     'app_verify', // Make sure you have a route named 'app_verify'
        //     ['vkey' => $user->getVkey()],
        //     UrlGeneratorInterface::ABSOLUTE_URL
        // );

        // // Send verification email
        // $emailBody = $twig->render('emails/verify_email.html.twig', [
        //     'username' => $username,
        //     'verifyUrl' => $verificationUrl,
        //     'app_name' => 'CohabitApp'
        // ]);

        // $email = (new Email())
        //     ->from($_ENV['MAILER_FROM'])
        //     ->to($userEmail)
        //     ->subject('Verify your email address')
        //     ->html($emailBody);

        // $mailer->send($email);

        return $this->json([
            'status' => true,
            'message' => 'Registration successful. Please check your email to verify your account.'
        ]);
    }
}