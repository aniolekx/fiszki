<?php

declare(strict_types=1);

namespace App\Application\User\Command;

final readonly class RegisterUserCommand
{
    public function __construct(
        private string $email,
        private string $password,
        private bool $agreeTerms
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getAgreeTerms(): bool
    {
        return $this->agreeTerms;
    }
}
