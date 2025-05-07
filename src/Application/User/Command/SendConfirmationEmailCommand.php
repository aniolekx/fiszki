<?php

declare(strict_types=1);

namespace App\Application\User\Command;

final readonly class SendConfirmationEmailCommand
{
    public function __construct(
        private string $email,
        private string $confirmationToken
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getConfirmationToken(): string
    {
        return $this->confirmationToken;
    }
}
