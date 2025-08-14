<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\CreditTransaction;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CreditTransactionTest extends TestCase
{
    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        $this->user = new User('test@example.com');
        $this->admin = new User('admin@example.com');
    }

    public function testConstructorSetsAllValues(): void
    {
        $transaction = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_ADMIN_GRANT,
            500,
            1500,
            'Bonus for activity',
            $this->admin
        );

        $this->assertSame($this->user, $transaction->getUser());
        $this->assertEquals(CreditTransaction::TYPE_ADMIN_GRANT, $transaction->getType());
        $this->assertEquals(500, $transaction->getAmount());
        $this->assertEquals(1500, $transaction->getBalanceAfter());
        $this->assertEquals('Bonus for activity', $transaction->getDescription());
        $this->assertSame($this->admin, $transaction->getPerformedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->getCreatedAt());
        $this->assertNull($transaction->getId());
    }

    public function testConstructorWithoutOptionalParameters(): void
    {
        $transaction = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_INITIAL,
            1000,
            1000
        );

        $this->assertSame($this->user, $transaction->getUser());
        $this->assertEquals(CreditTransaction::TYPE_INITIAL, $transaction->getType());
        $this->assertEquals(1000, $transaction->getAmount());
        $this->assertEquals(1000, $transaction->getBalanceAfter());
        $this->assertNull($transaction->getDescription());
        $this->assertNull($transaction->getPerformedBy());
    }

    public function testTransactionTypes(): void
    {
        $this->assertEquals('initial', CreditTransaction::TYPE_INITIAL);
        $this->assertEquals('admin_grant', CreditTransaction::TYPE_ADMIN_GRANT);
        $this->assertEquals('ai_generation', CreditTransaction::TYPE_AI_GENERATION);
        $this->assertEquals('refund', CreditTransaction::TYPE_REFUND);
        $this->assertEquals('bonus', CreditTransaction::TYPE_BONUS);
    }

    public function testIsDebitReturnsTrueForNegativeAmount(): void
    {
        $transaction = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_AI_GENERATION,
            -100,
            400
        );

        $this->assertTrue($transaction->isDebit());
        $this->assertFalse($transaction->isCredit());
    }

    public function testIsCreditReturnsTrueForPositiveAmount(): void
    {
        $transaction = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_ADMIN_GRANT,
            200,
            700
        );

        $this->assertTrue($transaction->isCredit());
        $this->assertFalse($transaction->isDebit());
    }

    public function testIsDebitAndIsCreditForZeroAmount(): void
    {
        $transaction = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_ADMIN_GRANT,
            0,
            500
        );

        $this->assertFalse($transaction->isDebit());
        $this->assertFalse($transaction->isCredit());
    }

    public function testMetadataGetterAndSetter(): void
    {
        $transaction = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_AI_GENERATION,
            -100,
            400
        );

        $this->assertNull($transaction->getMetadata());

        $metadata = [
            'session_id' => 123,
            'model' => 'gpt-3.5-turbo',
            'tokens' => 1500
        ];

        $transaction->setMetadata($metadata);
        $this->assertEquals($metadata, $transaction->getMetadata());

        $transaction->setMetadata(null);
        $this->assertNull($transaction->getMetadata());
    }

    public function testDifferentTransactionScenarios(): void
    {
        // Initial credits
        $initial = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_INITIAL,
            500,
            500
        );
        $this->assertEquals(500, $initial->getAmount());
        $this->assertTrue($initial->isCredit());

        // AI generation charge
        $aiCharge = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_AI_GENERATION,
            -100,
            400,
            'Generated 10 flashcards'
        );
        $this->assertEquals(-100, $aiCharge->getAmount());
        $this->assertTrue($aiCharge->isDebit());

        // Admin grant
        $adminGrant = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_ADMIN_GRANT,
            1000,
            1400,
            'Monthly bonus',
            $this->admin
        );
        $this->assertEquals(1000, $adminGrant->getAmount());
        $this->assertTrue($adminGrant->isCredit());
        $this->assertSame($this->admin, $adminGrant->getPerformedBy());

        // Refund
        $refund = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_REFUND,
            100,
            1500,
            'Refund for failed generation'
        );
        $this->assertEquals(100, $refund->getAmount());
        $this->assertTrue($refund->isCredit());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $metadata = ['key' => 'value'];
        $transaction = new CreditTransaction(
            $this->user,
            CreditTransaction::TYPE_BONUS,
            250,
            750,
            'Test description',
            $this->admin
        );
        $transaction->setMetadata($metadata);

        $this->assertNull($transaction->getId());
        $this->assertSame($this->user, $transaction->getUser());
        $this->assertEquals(CreditTransaction::TYPE_BONUS, $transaction->getType());
        $this->assertEquals(250, $transaction->getAmount());
        $this->assertEquals(750, $transaction->getBalanceAfter());
        $this->assertEquals('Test description', $transaction->getDescription());
        $this->assertSame($this->admin, $transaction->getPerformedBy());
        $this->assertEquals($metadata, $transaction->getMetadata());
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->getCreatedAt());
    }
}