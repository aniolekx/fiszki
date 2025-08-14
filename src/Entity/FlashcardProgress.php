<?php

namespace App\Entity;

use App\Repository\FlashcardProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlashcardProgressRepository::class)]
#[ORM\Table(name: 'flashcard_progress')]
#[ORM\UniqueConstraint(name: 'unique_user_flashcard', columns: ['user_id', 'flashcard_id'])]
class FlashcardProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Flashcard::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Flashcard $flashcard = null;

    #[ORM\ManyToOne(targetEntity: StudySession::class, inversedBy: 'flashcardProgresses')]
    private ?StudySession $studySession = null;

    #[ORM\Column]
    private ?int $repetitions = 0;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $easeFactor = 2.5; // EF in SM-2 algorithm

    #[ORM\Column]
    private ?int $interval = 0; // Days until next review

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastReviewedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $nextReviewAt = null;

    #[ORM\Column]
    private ?int $consecutiveCorrect = 0;

    #[ORM\Column]
    private ?int $totalAttempts = 0;

    #[ORM\Column]
    private ?int $correctAttempts = 0;

    #[ORM\Column(nullable: true)]
    private ?int $lastQuality = null; // 0-5 rating from last review

    public function __construct()
    {
        $this->lastReviewedAt = new \DateTime();
        $this->nextReviewAt = new \DateTime();
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

    public function getFlashcard(): ?Flashcard
    {
        return $this->flashcard;
    }

    public function setFlashcard(?Flashcard $flashcard): static
    {
        $this->flashcard = $flashcard;
        return $this;
    }

    public function getStudySession(): ?StudySession
    {
        return $this->studySession;
    }

    public function setStudySession(?StudySession $studySession): static
    {
        $this->studySession = $studySession;
        return $this;
    }

    public function getRepetitions(): ?int
    {
        return $this->repetitions;
    }

    public function setRepetitions(int $repetitions): static
    {
        $this->repetitions = $repetitions;
        return $this;
    }

    public function getEaseFactor(): ?float
    {
        return $this->easeFactor;
    }

    public function setEaseFactor(float $easeFactor): static
    {
        $this->easeFactor = $easeFactor;
        return $this;
    }

    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function setInterval(int $interval): static
    {
        $this->interval = $interval;
        return $this;
    }

    public function getLastReviewedAt(): ?\DateTimeInterface
    {
        return $this->lastReviewedAt;
    }

    public function setLastReviewedAt(\DateTimeInterface $lastReviewedAt): static
    {
        $this->lastReviewedAt = $lastReviewedAt;
        return $this;
    }

    public function getNextReviewAt(): ?\DateTimeInterface
    {
        return $this->nextReviewAt;
    }

    public function setNextReviewAt(\DateTimeInterface $nextReviewAt): static
    {
        $this->nextReviewAt = $nextReviewAt;
        return $this;
    }

    public function getConsecutiveCorrect(): ?int
    {
        return $this->consecutiveCorrect;
    }

    public function setConsecutiveCorrect(int $consecutiveCorrect): static
    {
        $this->consecutiveCorrect = $consecutiveCorrect;
        return $this;
    }

    public function getTotalAttempts(): ?int
    {
        return $this->totalAttempts;
    }

    public function setTotalAttempts(int $totalAttempts): static
    {
        $this->totalAttempts = $totalAttempts;
        return $this;
    }

    public function incrementTotalAttempts(): static
    {
        $this->totalAttempts++;
        return $this;
    }

    public function getCorrectAttempts(): ?int
    {
        return $this->correctAttempts;
    }

    public function setCorrectAttempts(int $correctAttempts): static
    {
        $this->correctAttempts = $correctAttempts;
        return $this;
    }

    public function incrementCorrectAttempts(): static
    {
        $this->correctAttempts++;
        return $this;
    }

    public function getLastQuality(): ?int
    {
        return $this->lastQuality;
    }

    public function setLastQuality(?int $lastQuality): static
    {
        $this->lastQuality = $lastQuality;
        return $this;
    }

    public function getAccuracy(): float
    {
        if ($this->totalAttempts === 0) {
            return 0;
        }
        return round(($this->correctAttempts / $this->totalAttempts) * 100, 2);
    }

    public function isDue(): bool
    {
        return $this->nextReviewAt <= new \DateTime();
    }
}