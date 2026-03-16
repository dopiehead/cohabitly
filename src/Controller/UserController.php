<?php


namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class UserController extends AbstractController {

    #[Route('/user-details/{id}', name: 'app_user_details', methods: ['GET'])]
    public function detail(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findById($id);

        if (!$user) {
            return $this->json([
                'status' => false,
                'message' => 'user not found'
            ], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'image' => $user->getUserImage(),
            'email' => $user->getUserEmail(),
            'location' => $user->getUserLocation(),
            'address' => $user->getUserAddress(),
            'lga' => $user->getLga(),
            'date_joined' => $user->getCreatedAt(),
            'age' => $user->getUserDob(),
            'phone' => $user->getUserPhone(),
            'rating' =>$user->getUserRating(),
            'likes' =>$user->getUserLikes(),
            'shares' =>$user->getUserShares()
        ]);
    }



}