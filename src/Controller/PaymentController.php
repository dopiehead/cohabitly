<?php

use App\Entity\User;
use App\Service\PaystackService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


class PaymentController extends AbstractController
{   
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use App\Service\PaystackService;
    
    #[Route('/api/subscribe', name: 'app_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function subscribe(Request $request, PaystackService $paystack): Response
    {
        $user = $this->getUser();
    
        $userId = $user->getId();
        $amount = $request->request->get('price');
        $interval = $request->request->get('duration');
        $expiryDate = $request->request->get('expiry_date');
        $userType = $request->request->get('user_type');
    
        // Convert to kobo
        $amount = $amount * 100;
    
        // Create plan
        $plan = $paystack->createPlan(
            'User Plan '.$userId,
            $amount,
            $interval,
            $userId,
            $userType,
            $expiryDate
        );
    
        // Initialize payment
        $response = $paystack->initializePayment(
            $user->getEmail(),
            $amount,
            $this->generateUrl('payment_callback', [], 0)
        );
    
        return $this->redirect($response['data']['authorization_url']);
    }

    #[Route('/api/callback', name: 'app_subscribe_call_back', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]

    public function callback(Request $request, PaystackService $paystack)
{
    $reference = $request->query->get('reference');

    $payment = $paystack->verifyPayment($reference);

    if ($payment['data']['status'] === 'success') {

        // activate subscription
    }

    return new Response('Payment successful');
}


}