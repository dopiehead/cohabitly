<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Repository\ApplicationRepository;
use App\Repository\ProfileRepository;
use App\Repository\PropertyRepository;
use App\Repository\UserNotificationRepository;
use App\Service\ApplicationService;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/application')]
#[IsGranted('ROLE_USER')]
final class ApplicationController extends AbstractController
{
    // ─────────────────────────────────────────────
    // POST /api/application  — apply for a listing
    // ─────────────────────────────────────────────
    #[Route('', name: 'application_create', methods: ['POST'])]
    public function create(
        Request $request,
        PropertyRepository $propertyRepository,
        ApplicationRepository $applicationRepository,
        ProfileRepository $profileRepository,
        NotificationService $notificationService
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $listingId = $data['listing_id'] ?? null;
        if (!$listingId) {
            return $this->json(['status' => false, 'message' => 'listing_id is required'], 400);
        }

        $listing = $propertyRepository->find($listingId);
        if (!$listing) {
            return $this->json(['status' => false, 'message' => 'Listing not found'], 404);
        }

        if ($listing->getStatus() !== 'active') {
            return $this->json(['status' => false, 'message' => 'Listing is not active'], 422);
        }

        // Profile completeness gate
        $profile = $profileRepository->findByUser($user);
        if (!$profile || !$profile->isComplete()) {
            return $this->json(['status' => false, 'message' => 'Complete your profile before applying'], 403);
        }

        // One application per user per listing
        if ($applicationRepository->findExisting($listing, $user)) {
            return $this->json(['status' => false, 'message' => 'You have already applied for this listing'], 409);
        }

        $application = new Application();
        $application->setListing($listing);
        $application->setApplicant($user);
        $applicationRepository->save($application);

        // Notify listing owner
        $notificationService->notify(
            $listing->getOwner(),
            UserNotification::TYPE_APPLICATION_RECEIVED,
            [
                'listing_id'     => $listing->getId(),
                'application_id' => $application->getId(),
                'message'        => 'A new application was submitted on your listing.',
            ]
        );

        return $this->json(['status' => true, 'data' => $this->serialize($application)], 201);
    }

    // ─────────────────────────────────────────────
    // GET /api/application  — list applications
    // ?type=outgoing (default) | ?type=incoming
    // ─────────────────────────────────────────────
    #[Route('', name: 'application_index', methods: ['GET'])]
    public function index(
        Request $request,
        ApplicationRepository $applicationRepository,
        PropertyRepository $propertyRepository
    ): JsonResponse {
        /** @var User $user */
        $user  = $this->getUser();
        $type  = $request->query->get('type', 'outgoing');
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        if ($type === 'incoming') {
            // All applications across all listings owned by this user
            $listings     = $propertyRepository->findByOwner($user);
            $applications = [];
            foreach ($listings as $listing) {
                $apps = $applicationRepository->findByListing($listing, $page, $limit);
                array_push($applications, ...$apps);
            }
            $total = array_sum(array_map(
                fn($l) => $applicationRepository->countByListing($l),
                $listings
            ));
        } else {
            $applications = $applicationRepository->findByApplicant($user, $page, $limit);
            $total        = $applicationRepository->countByApplicant($user);
        }

        return $this->json([
            'status' => true,
            'data'   => array_map([$this, 'serialize'], $applications),
            'meta'   => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)],
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/application/:id
    // ─────────────────────────────────────────────
    #[Route('/{id}', name: 'application_show', methods: ['GET'])]
    public function show(int $id, ApplicationRepository $applicationRepository): JsonResponse
    {
        /** @var User $user */
        $user        = $this->getUser();
        $application = $applicationRepository->find($id);

        if (!$application) {
            return $this->json(['status' => false, 'message' => 'Application not found'], 404);
        }

        $isApplicant = $application->getApplicant()->getId() === $user->getId();
        $isOwner     = $application->getListing()->getOwner()->getId() === $user->getId();

        if (!$isApplicant && !$isOwner) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->json(['status' => true, 'data' => $this->serialize($application)]);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/application/:id/withdraw
    // ─────────────────────────────────────────────
    #[Route('/{id}/withdraw', name: 'application_withdraw', methods: ['PATCH'])]
    public function withdraw(int $id, ApplicationRepository $applicationRepository): JsonResponse
    {
        /** @var User $user */
        $user        = $this->getUser();
        $application = $applicationRepository->find($id);

        if (!$application) {
            return $this->json(['status' => false, 'message' => 'Application not found'], 404);
        }

        if ($application->getApplicant()->getId() !== $user->getId()) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        if ($application->getStatus() !== Application::STATUS_PENDING) {
            return $this->json(['status' => false, 'message' => 'Only pending applications can be withdrawn'], 422);
        }

        $application->setStatus(Application::STATUS_WITHDRAWN);
        $applicationRepository->save($application);

        return $this->json(['status' => true, 'data' => $this->serialize($application)]);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/application/:id/accept  (owner only)
    // ─────────────────────────────────────────────
    #[Route('/{id}/accept', name: 'application_accept', methods: ['PATCH'])]
    public function accept(
        int $id,
        ApplicationRepository $applicationRepository,
        ApplicationService $applicationService
    ): JsonResponse {
        /** @var User $user */
        $user        = $this->getUser();
        $application = $applicationRepository->find($id);

        if (!$application) {
            return $this->json(['status' => false, 'message' => 'Application not found'], 404);
        }

        if ($application->getListing()->getOwner()->getId() !== $user->getId()) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        if ($application->getStatus() !== Application::STATUS_PENDING) {
            return $this->json(['status' => false, 'message' => 'Application is not pending'], 422);
        }

        $applicationService->accept($application);

        return $this->json(['status' => true, 'data' => $this->serialize($application)]);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/application/:id/reject  (owner only)
    // ─────────────────────────────────────────────
    #[Route('/{id}/reject', name: 'application_reject', methods: ['PATCH'])]
    public function reject(
        int $id,
        ApplicationRepository $applicationRepository,
        NotificationService $notificationService
    ): JsonResponse {
        /** @var User $user */
        $user        = $this->getUser();
        $application = $applicationRepository->find($id);

        if (!$application) {
            return $this->json(['status' => false, 'message' => 'Application not found'], 404);
        }

        if ($application->getListing()->getOwner()->getId() !== $user->getId()) {
            return $this->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        if ($application->getStatus() !== Application::STATUS_PENDING) {
            return $this->json(['status' => false, 'message' => 'Application is not pending'], 422);
        }

        $application->setStatus(Application::STATUS_REJECTED);
        $applicationRepository->save($application);

        $notificationService->notify(
            $application->getApplicant(),
            UserNotification::TYPE_APPLICATION_REJECTED,
            [
                'listing_id'     => $application->getListing()->getId(),
                'application_id' => $application->getId(),
                'message'        => 'Your application was rejected.',
            ]
        );

        return $this->json(['status' => true, 'data' => $this->serialize($application)]);
    }

    private function serialize(Application $a): array
    {
        return [
            'id'           => $a->getId(),
            'listing_id'   => $a->getListing()->getId(),
            'applicant_id' => $a->getApplicant()->getId(),
            'status'       => $a->getStatus(),
            'created_at'   => $a->getCreatedAt()->format(\DateTime::ATOM),
            'updated_at'   => $a->getUpdatedAt()->format(\DateTime::ATOM),
        ];
    }
}
