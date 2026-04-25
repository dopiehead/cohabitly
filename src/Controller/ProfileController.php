<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use App\Service\CloudinaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/profile')]
final class ProfileController extends AbstractController
{
    // ─────────────────────────────────────────────
    // POST /api/profile  — create profile
    // ─────────────────────────────────────────────
    #[Route('', name: 'profile_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        ProfileRepository $profileRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($profileRepository->findByUser($user)) {
            return $this->json(['status' => false, 'message' => 'Profile already exists. Use PATCH to update.'], 409);
        }

        $data    = json_decode($request->getContent(), true) ?? [];
        $profile = new Profile();
        $profile->setUser($user);
        $this->applyData($profile, $data);
        $profile->computeCompleteness();
        $profileRepository->save($profile);

        return $this->json(['status' => true, 'data' => $this->serialize($profile)], 201);
    }

    // ─────────────────────────────────────────────
    // GET /api/profile/me
    // ─────────────────────────────────────────────
    #[Route('/me', name: 'profile_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(ProfileRepository $profileRepository): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $profile = $profileRepository->findByUser($user);

        if (!$profile) {
            return $this->json(['status' => false, 'message' => 'Profile not found'], 404);
        }

        return $this->json(['status' => true, 'data' => $this->serialize($profile)]);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/profile/me
    // ─────────────────────────────────────────────
    #[Route('/me', name: 'profile_update', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(
        Request $request,
        ProfileRepository $profileRepository
    ): JsonResponse {
        /** @var User $user */
        $user    = $this->getUser();
        $profile = $profileRepository->findByUser($user);

        if (!$profile) {
            return $this->json(['status' => false, 'message' => 'Profile not found. Create one first.'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $this->applyData($profile, $data);
        $profile->computeCompleteness();
        $profile->touch();
        $profileRepository->save($profile);

        return $this->json(['status' => true, 'data' => $this->serialize($profile)]);
    }

    // ─────────────────────────────────────────────
    // GET /api/profile/:userId  — public profile
    // ─────────────────────────────────────────────
    #[Route('/{userId}', name: 'profile_public', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function publicProfile(int $userId, ProfileRepository $profileRepository, UserRepository $userRepository): JsonResponse
    {
        $targetUser = $userRepository->findById($userId);
        if (!$targetUser) {
            return $this->json(['status' => false, 'message' => 'User not found'], 404);
        }

        $profile = $profileRepository->findByUser($targetUser);
        if (!$profile) {
            return $this->json(['status' => false, 'message' => 'Profile not found'], 404);
        }

        return $this->json(['status' => true, 'data' => $this->serializePublic($profile)]);
    }

    // ─────────────────────────────────────────────
    // POST /api/profile/photo  — upload profile photo
    // ─────────────────────────────────────────────
    #[Route('/photo', name: 'profile_photo', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadPhoto(
        Request $request,
        ProfileRepository $profileRepository,
        CloudinaryService $cloudinary
    ): JsonResponse {
        /** @var User $user */
        $user    = $this->getUser();
        $profile = $profileRepository->findByUser($user);

        if (!$profile) {
            return $this->json(['status' => false, 'message' => 'Create a profile first'], 404);
        }

        $file = $request->files->get('photo');
        if (!$file) {
            return $this->json(['status' => false, 'message' => 'No photo file provided'], 400);
        }

        try {
            $url = $cloudinary->upload($file);
            $profile->setPhotoUrl($url);
            $profile->computeCompleteness();
            $profile->touch();
            $profileRepository->save($profile);

            return $this->json(['status' => true, 'data' => ['photo_url' => $url]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────
    private function applyData(Profile $profile, array $data): void
    {
        if (isset($data['full_name']))            $profile->setFullName($data['full_name']);
        if (isset($data['phone_number']))         $profile->setPhoneNumber($data['phone_number']);
        if (isset($data['date_of_birth']))        $profile->setDateOfBirth(new \DateTime($data['date_of_birth']));
        if (isset($data['gender']))               $profile->setGender($data['gender']);
        if (isset($data['occupation']))           $profile->setOccupation($data['occupation']);
        if (isset($data['employment_status']))    $profile->setEmploymentStatus($data['employment_status']);
        if (isset($data['monthly_income_range'])) $profile->setMonthlyIncomeRange($data['monthly_income_range']);
        if (isset($data['bio']))                  $profile->setBio($data['bio']);
    }

    private function serialize(Profile $p): array
    {
        return [
            'id'                   => $p->getId(),
            'user_id'              => $p->getUser()->getId(),
            'full_name'            => $p->getFullName(),
            'phone_number'         => $p->getPhoneNumber(),
            'date_of_birth'        => $p->getDateOfBirth()?->format('Y-m-d'),
            'gender'               => $p->getGender(),
            'occupation'           => $p->getOccupation(),
            'employment_status'    => $p->getEmploymentStatus(),
            'monthly_income_range' => $p->getMonthlyIncomeRange(),
            'bio'                  => $p->getBio(),
            'photo_url'            => $p->getPhotoUrl(),
            'is_complete'          => $p->isComplete(),
            'created_at'           => $p->getCreatedAt()->format(\DateTime::ATOM),
            'updated_at'           => $p->getUpdatedAt()->format(\DateTime::ATOM),
        ];
    }

    private function serializePublic(Profile $p): array
    {
        return [
            'user_id'           => $p->getUser()->getId(),
            'full_name'         => $p->getFullName(),
            'gender'            => $p->getGender(),
            'occupation'        => $p->getOccupation(),
            'employment_status' => $p->getEmploymentStatus(),
            'bio'               => $p->getBio(),
            'photo_url'         => $p->getPhotoUrl(),
        ];
    }
}
