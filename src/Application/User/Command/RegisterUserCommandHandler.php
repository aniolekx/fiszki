<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Entity\User;
use App\Repository\DoctrineUserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Application\User\Command\SendConfirmationEmailCommand;

#[AsMessageHandler]
final readonly class RegisterUserCommandHandler
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private DoctrineUserRepository $userRepository, // Assuming you have a UserRepository
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        // Basic validation (more complex validation should be in the form)
        if ($this->userRepository->findOneBy(['email' => $command->getEmail()])) {
            throw new \DomainException('User with this email already exists.');
        }

        if (!$command->getAgreeTerms()) {
             throw new \DomainException('You must agree to the terms.');
        }

        $user = new User($command->getEmail());
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $command->getPassword()
            )
        );

        $confirmationToken = Uuid::v4()->toRfc4122();
        $user->setConfirmationToken($confirmationToken);
        $user->setIsConfirmed(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new SendConfirmationEmailCommand($user->getEmail(), $confirmationToken));
    }
}
