<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserCreditsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: UserCreditsRepository::class)]
#[ORM\Table(name: 'user_credits')]
#[ORM\HasLifecycleCallbacks]
class UserCredits
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'credits', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::INTEGER)]
    private int $balance = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalEarned = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalSpent = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct(User $user, int $initialBalance = 500)
    {
        $this->user = $user;
        $this->balance = $initialBalance;
        $this->totalEarned = $initialBalance;
        $this->totalSpent = 0;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function addCredits(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        $this->balance += $amount;
        $this->totalEarned += $amount;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }

    public function deductCredits(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if ($this->balance < $amount) {
            throw new \RuntimeException('Insufficient credits');
        }
        
        $this->balance -= $amount;
        $this->totalSpent += $amount;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }

    public function hasEnoughCredits(int $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function getTotalEarned(): int
    {
        return $this->totalEarned;
    }

    public function getTotalSpent(): int
    {
        return $this->totalSpent;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }
}