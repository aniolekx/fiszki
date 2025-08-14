<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CreditTransaction;
use App\Entity\User;
use App\Entity\UserCredits;
use App\Repository\SystemSettingsRepository;
use App\Repository\UserCreditsRepository;
use Doctrine\ORM\EntityManagerInterface;

class CreditService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserCreditsRepository $creditsRepository,
        private readonly SystemSettingsRepository $settingsRepository
    ) {
    }

    public function initializeUserCredits(User $user): UserCredits
    {
        $defaultCredits = (int) $this->settingsRepository->getValue('default_credits', 500);
        
        $credits = new UserCredits($user, $defaultCredits);
        $this->entityManager->persist($credits);
        
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_INITIAL,
            $defaultCredits,
            $defaultCredits,
            'Kredyty początkowe'
        );
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
        
        return $credits;
    }

    public function getUserCredits(User $user): UserCredits
    {
        $credits = $this->creditsRepository->findByUser($user);
        
        if (!$credits) {
            $credits = $this->initializeUserCredits($user);
        }
        
        return $credits;
    }

    public function hasEnoughCredits(User $user, int $amount): bool
    {
        $credits = $this->getUserCredits($user);
        return $credits->hasEnoughCredits($amount);
    }

    public function chargeCredits(User $user, int $amount, string $description, ?array $metadata = null): void
    {
        $credits = $this->getUserCredits($user);
        
        if (!$credits->hasEnoughCredits($amount)) {
            throw new \RuntimeException('Niewystarczająca liczba kredytów');
        }
        
        $credits->deductCredits($amount);
        
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_AI_GENERATION,
            -$amount,
            $credits->getBalance(),
            $description
        );
        
        if ($metadata) {
            $transaction->setMetadata($metadata);
        }
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function grantCredits(User $user, int $amount, string $description, ?User $grantedBy = null): void
    {
        $credits = $this->getUserCredits($user);
        $credits->addCredits($amount);
        
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_ADMIN_GRANT,
            $amount,
            $credits->getBalance(),
            $description,
            $grantedBy
        );
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function refundCredits(User $user, int $amount, string $reason): void
    {
        $credits = $this->getUserCredits($user);
        $credits->addCredits($amount);
        
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_REFUND,
            $amount,
            $credits->getBalance(),
            'Zwrot: ' . $reason
        );
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }
}