<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\Deck;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('test@example.com');
    }

    public function testGettersAndSetters(): void
    {
        $this->assertEquals('test@example.com', $this->user->getEmail());
        $this->assertEquals('test@example.com', $this->user->getUserIdentifier());

        $password = 'hashedPassword123';
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testRoles(): void
    {
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        $this->user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testEmailVerification(): void
    {
        $this->assertFalse($this->user->isConfirmed());

        $this->user->setIsConfirmed(true);
        $this->assertTrue($this->user->isConfirmed());
    }

    public function testConfirmationToken(): void
    {
        $this->assertNull($this->user->getConfirmationToken());

        $token = 'confirmation-token-123';
        $this->user->setConfirmationToken($token);
        $this->assertEquals($token, $this->user->getConfirmationToken());

        $this->user->setConfirmationToken(null);
        $this->assertNull($this->user->getConfirmationToken());
    }



    public function testEraseCredentials(): void
    {
        $this->user->eraseCredentials();
        $this->assertTrue(true);
    }

    public function testIdIsNullInitially(): void
    {
        $this->assertNull($this->user->getId());
    }

    public function testUniqueEmail(): void
    {
        $user1 = new User('unique@example.com');

        $user2 = new User('unique@example.com');

        $this->assertEquals($user1->getEmail(), $user2->getEmail());
    }
}