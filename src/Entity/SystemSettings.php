<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SystemSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: SystemSettingsRepository::class)]
#[ORM\Table(name: 'system_settings')]
class SystemSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $settingKey;

    #[ORM\Column(type: Types::TEXT)]
    private string $value;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type = 'string';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    public const DEFAULT_CREDITS = 'default_credits';
    public const AI_GENERATION_COST = 'ai_generation_cost';
    public const OPENAI_TOTAL_TOKENS = 'openai_total_tokens';
    public const OPENAI_MONTHLY_LIMIT = 'openai_monthly_limit';
    public const SYSTEM_EMAIL = 'system_email';
    public const MAINTENANCE_MODE = 'maintenance_mode';

    public function __construct(string $settingKey, string $value, string $type = 'string')
    {
        $this->settingKey = $settingKey;
        $this->value = $value;
        $this->type = $type;
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTypedValue(): mixed
    {
        return match($this->type) {
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}