<?php

// src/Security/JWTAuthenticationSuccessHandler.php

namespace App\Security;

use App\Service\MercureJwtGenerator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JWTAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $jwtManager;
    private $mercureJwt;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        MercureJwtGenerator $mercureJwt
    ) {
        $this->jwtManager = $jwtManager;
        $this->mercureJwt = $mercureJwt;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();

        $jwt = $this->jwtManager->create($user);

        $mercureToken = $this->mercureJwt->generate(
            ["/user/{$user->getId()}"],
            ["/user/{$user->getId()}"]
        );

        return new JsonResponse([
            'token' => $jwt,
            // 'mercure_token' => $mercureToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ]
        ]);
    }
}