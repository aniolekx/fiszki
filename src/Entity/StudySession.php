<?php

namespace App\Entity;

use App\Repository\StudySessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudySessionRepository::class)]
class StudySession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Deck::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Deck $deck = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column]
    private ?int $totalCards = 0;

    #[ORM\Column]
    private ?int $reviewedCards = 0;

    #[ORM\Column]
    private ?int $correctAnswers = 0;

    #[ORM\Column(length: 20)]
    private ?string $status = 'in_progress'; // in_progress, completed, abandoned

    #[ORM\OneToMany(mappedBy: 'studySession', targetEntity: FlashcardProgress::class, cascade: ['persist', 'remove'])]
    private Collection $flashcardProgresses;

    public function __construct()
    {
        $this->flashcardProgresses = new ArrayCollection();
        $this->startedAt = new \DateTime();
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

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getTotalCards(): ?int
    {
        return $this->totalCards;
    }

    public function setTotalCards(int $totalCards): static
    {
        $this->totalCards = $totalCards;
        return $this;
    }

    public function getReviewedCards(): ?int
    {
        return $this->reviewedCards;
    }

    public function setReviewedCards(int $reviewedCards): static
    {
        $this->reviewedCards = $reviewedCards;
        return $this;
    }

    public function incrementReviewedCards(): static
    {
        $this->reviewedCards++;
        return $this;
    }

    public function getCorrectAnswers(): ?int
    {
        return $this->correctAnswers;
    }

    public function setCorrectAnswers(int $correctAnswers): static
    {
        $this->correctAnswers = $correctAnswers;
        return $this;
    }

    public function incrementCorrectAnswers(): static
    {
        $this->correctAnswers++;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getFlashcardProgresses(): Collection
    {
        return $this->flashcardProgresses;
    }

    public function addFlashcardProgress(FlashcardProgress $flashcardProgress): static
    {
        if (!$this->flashcardProgresses->contains($flashcardProgress)) {
            $this->flashcardProgresses->add($flashcardProgress);
            $flashcardProgress->setStudySession($this);
        }
        return $this;
    }

    public function removeFlashcardProgress(FlashcardProgress $flashcardProgress): static
    {
        if ($this->flashcardProgresses->removeElement($flashcardProgress)) {
            if ($flashcardProgress->getStudySession() === $this) {
                $flashcardProgress->setStudySession(null);
            }
        }
        return $this;
    }

    public function getAccuracy(): float
    {
        if ($this->reviewedCards === 0) {
            return 0;
        }
        return round(($this->correctAnswers / $this->reviewedCards) * 100, 2);
    }
}