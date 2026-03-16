<?php

// src/Service/SubscriptionService.php
namespace App\Service;

use App\Repository\SubscriptionRepository;

class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository
    ) {}

    public function getSubscriptionStatus(int $userId): array
    {
        $subscriptions = $this->subscriptionRepository->findActiveByUser($userId);

        if (!$subscriptions) {
            return [
                'status' => 'none',
                'message' => 'You do not have an active subscription'
            ];
        }

        $messages = [];

        foreach ($subscriptions as $subscription) {
            if ($subscription->isExpired()) {
                $messages[] = [
                    'type' => 'expired',
                    'text' => 'Subscription expired on '.$subscription->getExpiryDate()->format('Y-m-d')
                ];
            } elseif ($subscription->getDaysLeft() <= 5) {
                $messages[] = [
                    'type' => 'warning',
                    'text' => 'Subscription expires in '.$subscription->getDaysLeft().' days'
                ];
            } else {
                $messages[] = [
                    'type' => 'active',
                    'text' => 'Subscription active until '.$subscription->getExpiryDate()->format('Y-m-d')
                ];
            }
        }

        return [
            'status' => 'found',
            'messages' => $messages
        ];
    }
}
