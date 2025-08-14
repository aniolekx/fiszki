<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\CreditTransaction;
use App\Entity\User;
use App\Entity\UserCredits;
use App\Repository\SystemSettingsRepository;
use App\Repository\UserCreditsRepository;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CreditServiceTest extends TestCase
{
    private CreditService $creditService;
    private EntityManagerInterface|MockObject $entityManager;
    private UserCreditsRepository|MockObject $creditsRepository;
    private SystemSettingsRepository|MockObject $settingsRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->creditsRepository = $this->createMock(UserCreditsRepository::class);
        $this->settingsRepository = $this->createMock(SystemSettingsRepository::class);

        $this->creditService = new CreditService(
            $this->entityManager,
            $this->creditsRepository,
            $this->settingsRepository
        );
    }

    public function testInitializeUserCreditsCreatesNewCreditsWithDefaultAmount(): void
    {
        $user = new User('test@example.com');
        $defaultCredits = 500;

        $this->settingsRepository->expects($this->once())
            ->method('getValue')
            ->with('default_credits', 500)
            ->willReturn($defaultCredits);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr(
                $this->isInstanceOf(UserCredits::class),
                $this->isInstanceOf(CreditTransaction::class)
            ));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $credits = $this->creditService->initializeUserCredits($user);

        $this->assertInstanceOf(UserCredits::class, $credits);
        $this->assertEquals($defaultCredits, $credits->getBalance());
        $this->assertEquals($defaultCredits, $credits->getTotalEarned());
        $this->assertEquals(0, $credits->getTotalSpent());
    }

    public function testGetUserCreditsReturnsExistingCredits(): void
    {
        $user = new User('test@example.com');
        $existingCredits = new UserCredits($user, 300);

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($existingCredits);

        $credits = $this->creditService->getUserCredits($user);

        $this->assertSame($existingCredits, $credits);
    }

    public function testGetUserCreditsCreatesNewCreditsIfNotExist(): void
    {
        $user = new User('test@example.com');

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn(null);

        $this->settingsRepository->expects($this->once())
            ->method('getValue')
            ->with('default_credits', 500)
            ->willReturn(500);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $credits = $this->creditService->getUserCredits($user);

        $this->assertInstanceOf(UserCredits::class, $credits);
        $this->assertEquals(500, $credits->getBalance());
    }

    public function testHasEnoughCreditsReturnsTrueWhenSufficient(): void
    {
        $user = new User('test@example.com');
        $userCredits = new UserCredits($user, 500);

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($userCredits);

        $result = $this->creditService->hasEnoughCredits($user, 100);

        $this->assertTrue($result);
    }

    public function testHasEnoughCreditsReturnsFalseWhenInsufficient(): void
    {
        $user = new User('test@example.com');
        $userCredits = new UserCredits($user, 50);

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($userCredits);

        $result = $this->creditService->hasEnoughCredits($user, 100);

        $this->assertFalse($result);
    }

    public function testChargeCreditsDeductsAmountAndCreatesTransaction(): void
    {
        $user = new User('test@example.com');
        $userCredits = new UserCredits($user, 500);

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($userCredits);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(CreditTransaction::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->creditService->chargeCredits($user, 100, 'Test charge');

        $this->assertEquals(400, $userCredits->getBalance());
        $this->assertEquals(100, $userCredits->getTotalSpent());
    }

    public function testChargeCreditsThrowsExceptionWhenInsufficientFunds(): void
    {
        $user = new User('test@example.com');
        $userCredits = new UserCredits($user, 50);

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($userCredits);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Niewystarczająca liczba kredytów');

        $this->creditService->chargeCredits($user, 100, 'Test charge');
    }

    public function testGrantCreditsAddsAmountAndCreatesTransaction(): void
    {
        $user = new User('test@example.com');
        $admin = new User('admin@example.com');
        $userCredits = new UserCredits($user, 100);

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($userCredits);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(CreditTransaction::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->creditService->grantCredits($user, 200, 'Bonus', $admin);

        $this->assertEquals(300, $userCredits->getBalance());
        $this->assertEquals(300, $userCredits->getTotalEarned());
    }

    public function testRefundCreditsAddsAmountBackAndCreatesTransaction(): void
    {
        $user = new User('test@example.com');
        $userCredits = new UserCredits($user, 100);

        $this->creditsRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($userCredits);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($transaction) {
                return $transaction instanceof CreditTransaction
                    && $transaction->getType() === CreditTransaction::TYPE_REFUND
                    && $transaction->getAmount() === 50
                    && str_contains($transaction->getDescription(), 'Zwrot');
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->creditService->refundCredits($user, 50, 'Błąd generacji');

        $this->assertEquals(150, $userCredits->getBalance());
    }
}