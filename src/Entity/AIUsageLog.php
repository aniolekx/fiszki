<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AIUsageLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: AIUsageLogRepository::class)]
#[ORM\Table(name: 'ai_usage_logs')]
class AIUsageLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: GenerationSession::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?GenerationSession $generationSession = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $tokensUsed;

    #[ORM\Column(type: Types::INTEGER)]
    private int $creditsCharged;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $model = 'gpt-3.5-turbo';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true)]
    private ?string $estimatedCost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $prompt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $response = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct(
        User $user,
        int $tokensUsed,
        int $creditsCharged,
        ?GenerationSession $generationSession = null
    ) {
        $this->user = $user;
        $this->tokensUsed = $tokensUsed;
        $this->creditsCharged = $creditsCharged;
        $this->generationSession = $generationSession;
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

    public function getGenerationSession(): ?GenerationSession
    {
        return $this->generationSession;
    }

    public function getTokensUsed(): int
    {
        return $this->tokensUsed;
    }

    public function getCreditsCharged(): int
    {
        return $this->creditsCharged;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getEstimatedCost(): ?float
    {
        return $this->estimatedCost ? (float) $this->estimatedCost : null;
    }

    public function setEstimatedCost(?float $estimatedCost): self
    {
        $this->estimatedCost = $estimatedCost !== null ? (string) $estimatedCost : null;
        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): self
    {
        $this->response = $response;
        return $this;
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
}