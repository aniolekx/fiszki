<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Repository\DoctrineUserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AsMessageHandler]
final readonly class LoginCommandHandler
{
    public function __construct(
        private DoctrineUserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(LoginCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $command->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }
    }
} 