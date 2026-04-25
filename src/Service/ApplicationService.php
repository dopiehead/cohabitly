<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Conversation;
use App\Entity\PropertyList;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Repository\ApplicationRepository;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicationRepository $applicationRepository,
        private ConversationRepository $conversationRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Accept an application atomically:
     * - accept the target application
     * - reject all other pending applications for the same listing
     * - close the listing
     * - send notifications
     * - create a Conversation
     */
    public function accept(Application $application): void
    {
        $this->em->beginTransaction();

        try {
            $listing = $application->getListing();
            $applicant = $application->getApplicant();
            $owner = $listing->getOwner();

            // 1. Accept this application
            $application->setStatus(Application::STATUS_ACCEPTED);
            $this->em->persist($application);

            // 2. Reject all other pending applications for the same listing
            $others = $this->applicationRepository->findByListing($listing, 1, 1000);
            foreach ($others as $other) {
                if ($other->getId() !== $application->getId()
                    && $other->getStatus() === Application::STATUS_PENDING
                ) {
                    $other->setStatus(Application::STATUS_REJECTED);
                    $this->em->persist($other);

                    // Notify rejected applicants
                    $this->notificationService->notify(
                        $other->getApplicant(),
                        UserNotification::TYPE_APPLICATION_REJECTED,
                        [
                            'listing_id'     => $listing->getId(),
                            'application_id' => $other->getId(),
                            'message'        => 'Your application was not successful.',
                        ]
                    );
                }
            }

            // 3. Close the listing
            $listing->setStatus(PropertyList::STATUS_CLOSED);
            $this->em->persist($listing);

            // 4. Notify accepted applicant
            $this->notificationService->notify(
                $applicant,
                UserNotification::TYPE_APPLICATION_ACCEPTED,
                [
                    'listing_id'     => $listing->getId(),
                    'application_id' => $application->getId(),
                    'message'        => 'Your application has been accepted!',
                ]
            );

            // 5. Create Conversation if not already existing
            $existing = $this->conversationRepository->findExisting($listing, $owner, $applicant);
            if (!$existing) {
                $conversation = new Conversation();
                $conversation->setListing($listing);
                $conversation->setOwner($owner);
                $conversation->setApplicant($applicant);
                $this->em->persist($conversation);
            }

            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
