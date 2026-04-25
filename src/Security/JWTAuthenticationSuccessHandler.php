<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MercureJwtGenerator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JWTAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private MercureJwtGenerator $mercureJwt,
        private UserRepository $userRepository
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        /** @var User $user */
        $user = $token->getUser();

        $accessToken = $this->jwtManager->create($user);

        // Issue and store hashed refresh token
        $rawRefresh    = bin2hex(random_bytes(32));
        $hashedRefresh = hash('sha256', $rawRefresh);
        $user->setRefreshToken($hashedRefresh);
        $this->userRepository->save($user);

        $mercureToken = $this->mercureJwt->generate(
            ["/user/{$user->getId()}"],
            ["/user/{$user->getId()}"]
        );

        return new JsonResponse([
            'status'        => true,
            'access_token'  => $accessToken,
            'refresh_token' => $rawRefresh,
            'user'          => [
                'id'    => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
                'role'  => $user->getUserRole(),
            ],
        ]);
    }
}
