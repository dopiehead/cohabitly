<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendVerificationEmail(
        string $to,
        string $username,
        string $verifyUrl
    ): void {
        $html = $this->twig->render('emails/verify_email.html.twig', [
            'username' => $username,
            'verifyUrl' => $verifyUrl,
            'app_name'  => 'Cohabitly',
        ]);

        $email = (new Email())
            ->from('no-reply@bookstore.test')
            ->to($to)
            ->subject('Verify your email address')
            ->html($html);

        $this->mailer->send($email);
    }
    
    
    
    public function sendResetPasswordEmail(string $to, string $resetUrl): void
    {
        $email = (new Email())
            ->from('no-reply@yourapp.com')
            ->to($to)
            ->subject('Reset your password')
            ->html(
                "<p>Click <a href=\"{$resetUrl}\">here</a> to reset your password.</p>"
            );

        $this->mailer->send($email);
    }




}
