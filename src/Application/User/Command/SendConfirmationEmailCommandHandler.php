<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
final readonly class SendConfirmationEmailCommandHandler
{
    private const TOKEN_LIFETIME_SECONDS = 24 * 3600; // 24 hours

    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function __invoke(SendConfirmationEmailCommand $command): void
    {
        $email = (new Email())
            ->from('no-reply@fiszki.local') // Replace with your sender email
            ->to($command->getEmail())
            ->subject('Please Confirm Your Email Address')
            ->html($this->twig->render('emails/confirmation_email.html.twig', [
                'confirmationToken' => $command->getConfirmationToken(),
                'email' => $command->getEmail(),
                'tokenLifetime' => self::TOKEN_LIFETIME_SECONDS,
            ]));

        $this->mailer->send($email);
    }
}
