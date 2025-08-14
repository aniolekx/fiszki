<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CreditTransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: CreditTransactionRepository::class)]
#[ORM\Table(name: 'credit_transactions')]
class CreditTransaction
{
    public const TYPE_INITIAL = 'initial';
    public const TYPE_ADMIN_GRANT = 'admin_grant';
    public const TYPE_AI_GENERATION = 'ai_generation';
    public const TYPE_REFUND = 'refund';
    public const TYPE_BONUS = 'bonus';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type;

    #[ORM\Column(type: Types::INTEGER)]
    private int $amount;

    #[ORM\Column(type: Types::INTEGER)]
    private int $balanceAfter;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $performedBy = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct(
        User $user,
        string $type,
        int $amount,
        int $balanceAfter,
        ?string $description = null,
        ?User $performedBy = null
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->amount = $amount;
        $this->balanceAfter = $balanceAfter;
        $this->description = $description;
        $this->performedBy = $performedBy;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getBalanceAfter(): int
    {
        return $this->balanceAfter;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPerformedBy(): ?User
    {
        return $this->performedBy;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }
}