<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PaystackService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PaymentController extends AbstractController
{
    #[Route('/api/subscribe', name: 'app_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function subscribe(Request $request, PaystackService $paystack): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $amount     = $request->request->get('price');
        $interval   = $request->request->get('duration');
        $expiryDate = $request->request->get('expiry_date');
        $userType   = $request->request->get('user_type');

        // Convert to kobo
        $amount = $amount * 100;

        $paystack->createPlan(
            'User Plan ' . $user->getId(),
            $amount,
            $interval,
            $user->getId(),
            $userType,
            $expiryDate
        );

        $response = $paystack->initializePayment(
            $user->getUserEmail(),
            $amount,
            $this->generateUrl('app_subscribe_call_back', [], 0)
        );

        return $this->redirect($response['data']['authorization_url']);
    }

    #[Route('/api/callback', name: 'app_subscribe_call_back', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function callback(Request $request, PaystackService $paystack): Response
    {
        $reference = $request->query->get('reference');
        $payment   = $paystack->verifyPayment($reference);

        if ($payment['data']['status'] === 'success') {
            // activate subscription
        }

        return new Response('Payment successful');
    }
}
