<?php

namespace App\Entity;

use App\Repository\GenerationSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GenerationSessionRepository::class)]
class GenerationSession
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Deck::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Deck $deck = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $inputText = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $generatedFlashcards = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $acceptedFlashcards = [];

    #[ORM\Column(nullable: true)]
    private ?int $tokensUsed = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true)]
    private ?string $costUsd = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): static
    {
        $this->deck = $deck;

        return $this;
    }

    public function getInputText(): ?string
    {
        return $this->inputText;
    }

    public function setInputText(string $inputText): static
    {
        $this->inputText = $inputText;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_FAILED])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        
        $this->status = $status;

        if ($status === self::STATUS_COMPLETED || $status === self::STATUS_FAILED) {
            $this->completedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getGeneratedFlashcards(): array
    {
        return $this->generatedFlashcards;
    }

    public function setGeneratedFlashcards(array $generatedFlashcards): static
    {
        $this->generatedFlashcards = $generatedFlashcards;

        return $this;
    }

    public function getAcceptedFlashcards(): array
    {
        return $this->acceptedFlashcards;
    }

    public function setAcceptedFlashcards(array $acceptedFlashcards): static
    {
        $this->acceptedFlashcards = $acceptedFlashcards;

        return $this;
    }

    public function getTokensUsed(): ?int
    {
        return $this->tokensUsed;
    }

    public function setTokensUsed(?int $tokensUsed): static
    {
        $this->tokensUsed = $tokensUsed;

        return $this;
    }

    public function getCostUsd(): ?string
    {
        return $this->costUsd;
    }

    public function setCostUsd(?string $costUsd): static
    {
        $this->costUsd = $costUsd;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    /**
     * Calculate acceptance rate for this session
     */
    public function getAcceptanceRate(): ?float
    {
        if (empty($this->generatedFlashcards)) {
            return null;
        }

        return (count($this->acceptedFlashcards) / count($this->generatedFlashcards)) * 100;
    }
}
