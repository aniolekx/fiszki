<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\UserCredits;
use PHPUnit\Framework\TestCase;

class UserCreditsTest extends TestCase
{
    private User $user;
    private UserCredits $userCredits;

    protected function setUp(): void
    {
        $this->user = new User('test@example.com');
        $this->userCredits = new UserCredits($this->user, 500);
    }

    public function testConstructorSetsInitialValues(): void
    {
        $credits = new UserCredits($this->user, 1000);
        
        $this->assertSame($this->user, $credits->getUser());
        $this->assertEquals(1000, $credits->getBalance());
        $this->assertEquals(1000, $credits->getTotalEarned());
        $this->assertEquals(0, $credits->getTotalSpent());
        $this->assertInstanceOf(\DateTimeInterface::class, $credits->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $credits->getUpdatedAt());
    }

    public function testAddCreditsIncreasesBalance(): void
    {
        $initialBalance = $this->userCredits->getBalance();
        $initialEarned = $this->userCredits->getTotalEarned();
        
        $this->userCredits->addCredits(200);
        
        $this->assertEquals($initialBalance + 200, $this->userCredits->getBalance());
        $this->assertEquals($initialEarned + 200, $this->userCredits->getTotalEarned());
    }

    public function testAddCreditsThrowsExceptionForNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');
        
        $this->userCredits->addCredits(-100);
    }

    public function testDeductCreditsDecreasesBalance(): void
    {
        $initialBalance = $this->userCredits->getBalance();
        $initialSpent = $this->userCredits->getTotalSpent();
        
        $this->userCredits->deductCredits(100);
        
        $this->assertEquals($initialBalance - 100, $this->userCredits->getBalance());
        $this->assertEquals($initialSpent + 100, $this->userCredits->getTotalSpent());
    }

    public function testDeductCreditsThrowsExceptionForNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');
        
        $this->userCredits->deductCredits(-50);
    }

    public function testDeductCreditsThrowsExceptionForInsufficientBalance(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient credits');
        
        $this->userCredits->deductCredits(600); // Balance is 500
    }

    public function testHasEnoughCreditsReturnsTrueWhenSufficient(): void
    {
        $this->assertTrue($this->userCredits->hasEnoughCredits(500));
        $this->assertTrue($this->userCredits->hasEnoughCredits(400));
        $this->assertTrue($this->userCredits->hasEnoughCredits(0));
    }

    public function testHasEnoughCreditsReturnsFalseWhenInsufficient(): void
    {
        $this->assertFalse($this->userCredits->hasEnoughCredits(501));
        $this->assertFalse($this->userCredits->hasEnoughCredits(1000));
    }

    public function testMultipleTransactionsCalculateCorrectly(): void
    {
        // Starting with 500
        $this->userCredits->addCredits(300);    // 800 balance, 800 earned
        $this->userCredits->deductCredits(200); // 600 balance, 200 spent
        $this->userCredits->addCredits(100);    // 700 balance, 900 earned
        $this->userCredits->deductCredits(50);  // 650 balance, 250 spent
        
        $this->assertEquals(650, $this->userCredits->getBalance());
        $this->assertEquals(900, $this->userCredits->getTotalEarned());
        $this->assertEquals(250, $this->userCredits->getTotalSpent());
    }

    public function testUpdatedAtChangesOnOperations(): void
    {
        $initialUpdatedAt = $this->userCredits->getUpdatedAt();
        
        // Sleep for a tiny bit to ensure time difference
        usleep(1000);
        
        $this->userCredits->addCredits(100);
        $afterAddUpdatedAt = $this->userCredits->getUpdatedAt();
        
        $this->assertGreaterThan($initialUpdatedAt, $afterAddUpdatedAt);
        
        usleep(1000);
        
        $this->userCredits->deductCredits(50);
        $afterDeductUpdatedAt = $this->userCredits->getUpdatedAt();
        
        $this->assertGreaterThan($afterAddUpdatedAt, $afterDeductUpdatedAt);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $this->assertSame($this->user, $this->userCredits->getUser());
        $this->assertEquals(500, $this->userCredits->getBalance());
        $this->assertEquals(500, $this->userCredits->getTotalEarned());
        $this->assertEquals(0, $this->userCredits->getTotalSpent());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->userCredits->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->userCredits->getUpdatedAt());
        $this->assertNull($this->userCredits->getId());
    }
}