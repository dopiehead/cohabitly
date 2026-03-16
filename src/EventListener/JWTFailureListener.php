<?php
// src/EventListener/JWTFailureListener.php
namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;

class JWTFailureListener
{
    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event)
    {
        $data = [
            'status' => 'error',
            'message' => $event->getException()->getMessage()
        ];

        $event->setResponse(new \Symfony\Component\HttpFoundation\JsonResponse($data, 401));
    }
}