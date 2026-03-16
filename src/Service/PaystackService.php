<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Subscription;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaystackService
{
    private HttpClientInterface $client;
    private string $secretKey;
    private EntityManagerInterface $entityManager;

    public function __construct(
        HttpClientInterface $client,
        EntityManagerInterface $entityManager,
        string $paystackSecretKey
    ) {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->secretKey = $paystackSecretKey;
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Initialize payment
     */
    public function initializePayment(string $email, int $amount, string $callbackUrl)
    {
        $response = $this->client->request(
            'POST',
            'https://api.paystack.co/transaction/initialize',
            [
                'headers' => $this->headers(),
                'json' => [
                    'email' => $email,
                    'amount' => $amount,
                    'callback_url' => $callbackUrl
                ]
            ]
        );

        return $response->toArray();
    }

    /**
     * Verify payment
     */
    public function verifyPayment(string $reference)
    {
        $response = $this->client->request(
            'GET',
            'https://api.paystack.co/transaction/verify/' . $reference,
            [
                'headers' => $this->headers()
            ]
        );

        return $response->toArray();
    }

    /**
     * Create Paystack Plan and store in DB
     */
    public function createPlan(
        string $name,
        int $amount,
        string $interval,
        int $userId,
        string $userType,
        ?string $expiryDate = null
    ) {

        $response = $this->client->request(
            'POST',
            'https://api.paystack.co/plan',
            [
                'headers' => $this->headers(),
                'json' => [
                    'name' => $name,
                    'amount' => $amount,
                    'interval' => $interval
                ]
            ]
        );

        $data = $response->toArray();

        if ($data['status'] === true) {

            $plan = new Subscription();
            $plan->setName($name);
            $plan->setAmount($amount);
            $plan->setInterval($interval);
            $plan->setPaystackPlanCode($data['data']['plan_code']);
            $plan->setUserId($userId);
            $plan->setUserType($userType);
            $plan->setCreatedAt(new \DateTime());

            if ($expiryDate) {
                $plan->setExpiryDate(new \DateTime($expiryDate));
            }

            $this->entityManager->persist($plan);
            $this->entityManager->flush();
        }

        return $data;
    }

    /**
     * Create Subscription
     */
    public function createSubscription(string $customerEmail, string $planCode)
    {
        $response = $this->client->request(
            'POST',
            'https://api.paystack.co/subscription',
            [
                'headers' => $this->headers(),
                'json' => [
                    'customer' => $customerEmail,
                    'plan' => $planCode
                ]
            ]
        );

        return $response->toArray();
    }
}