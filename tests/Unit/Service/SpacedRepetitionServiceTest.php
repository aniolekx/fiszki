<?php

namespace App\Tests\Unit\Service;

use App\Entity\FlashcardProgress;
use App\Entity\Flashcard;
use App\Entity\User;
use App\Service\SpacedRepetitionService;
use PHPUnit\Framework\TestCase;

class SpacedRepetitionServiceTest extends TestCase
{
    private SpacedRepetitionService $service;
    private FlashcardProgress $progress;

    protected function setUp(): void
    {
        $this->service = new SpacedRepetitionService();
        $this->progress = new FlashcardProgress();
        $this->progress->setUser(new User('test@example.com'));
        $this->progress->setFlashcard(new Flashcard());
    }

    public function testCalculateNextReviewWithPerfectResponse(): void
    {
        $this->service->calculateNextReview($this->progress, 5);

        $this->assertEquals(1, $this->progress->getRepetitions());
        $this->assertEquals(1, $this->progress->getInterval());
        $this->assertEquals(1, $this->progress->getTotalAttempts());
        $this->assertEquals(1, $this->progress->getCorrectAttempts());
        $this->assertEquals(1, $this->progress->getConsecutiveCorrect());
        $this->assertEquals(5, $this->progress->getLastQuality());
        $this->assertGreaterThan(2.5, $this->progress->getEaseFactor());
    }

    public function testCalculateNextReviewWithPoorResponse(): void
    {
        $this->progress->setRepetitions(3);
        $this->progress->setInterval(10);
        $this->progress->setConsecutiveCorrect(3);

        $this->service->calculateNextReview($this->progress, 1);

        $this->assertEquals(0, $this->progress->getRepetitions());
        $this->assertEquals(1, $this->progress->getInterval());
        $this->assertEquals(0, $this->progress->getConsecutiveCorrect());
        $this->assertEquals(1, $this->progress->getTotalAttempts());
        $this->assertEquals(0, $this->progress->getCorrectAttempts());
        $this->assertLessThan(2.5, $this->progress->getEaseFactor());
    }

    public function testCalculateNextReviewWithGoodResponse(): void
    {
        $this->progress->setRepetitions(1);
        $this->progress->setInterval(1);
        
        $this->service->calculateNextReview($this->progress, 4);

        $this->assertEquals(2, $this->progress->getRepetitions());
        $this->assertEquals(6, $this->progress->getInterval()); // Second repetition goes to 6 days
        $this->assertEquals(1, $this->progress->getConsecutiveCorrect());
        $this->assertEquals(1, $this->progress->getCorrectAttempts());
    }

    public function testProgressionOfIntervals(): void
    {
        // First review - perfect
        $this->service->calculateNextReview($this->progress, 5);
        $this->assertEquals(1, $this->progress->getInterval());

        // Second review - perfect
        $this->service->calculateNextReview($this->progress, 5);
        $this->assertEquals(6, $this->progress->getInterval());

        // Third review - perfect
        $this->service->calculateNextReview($this->progress, 5);
        $this->assertGreaterThan(6, $this->progress->getInterval());
    }

    public function testInvalidQualityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculateNextReview($this->progress, 6);
    }

    public function testNegativeQualityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculateNextReview($this->progress, -1);
    }

    public function testGetQualityDescription(): void
    {
        $this->assertEquals('Zupełnie nie pamiętam', $this->service->getQualityDescription(0));
        $this->assertEquals('Perfekcyjna odpowiedź', $this->service->getQualityDescription(5));
        $this->assertEquals('Poprawna odpowiedź z dużym wysiłkiem', $this->service->getQualityDescription(3));
    }

    public function testGetDueCards(): void
    {
        $now = new \DateTime();
        
        $progress1 = new FlashcardProgress();
        $progress1->setNextReviewAt((clone $now)->modify('-1 day')); // Due
        
        $progress2 = new FlashcardProgress();
        $progress2->setNextReviewAt((clone $now)->modify('+1 day')); // Not due
        
        $progress3 = new FlashcardProgress();
        $progress3->setNextReviewAt((clone $now)->modify('-2 hours')); // Due
        
        $dueCards = $this->service->getDueCards([$progress1, $progress2, $progress3]);
        
        $this->assertCount(2, $dueCards);
        $this->assertContains($progress1, $dueCards);
        $this->assertContains($progress3, $dueCards);
        $this->assertNotContains($progress2, $dueCards);
    }

    public function testCalculateSessionStats(): void
    {
        $cards = [];
        
        for ($i = 0; $i < 5; $i++) {
            $card = new FlashcardProgress();
            $card->setLastQuality($i + 1); // Qualities: 1, 2, 3, 4, 5
            $card->setRepetitions($i === 0 ? 0 : 2);
            $cards[] = $card;
        }
        
        $stats = $this->service->calculateSessionStats($cards);
        
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(3, $stats['correct']); // Quality >= 3
        $this->assertEquals(60, $stats['accuracy']); // 3/5 * 100
        $this->assertEquals(3, $stats['averageQuality']); // (1+2+3+4+5)/5
        $this->assertEquals(1, $stats['newCards']);
        $this->assertEquals(4, $stats['reviewCards']);
    }

    public function testEaseFactorMinimum(): void
    {
        $this->progress->setEaseFactor(1.5);
        
        // Very poor response should still keep EF at minimum 1.3
        $this->service->calculateNextReview($this->progress, 0);
        
        $this->assertGreaterThanOrEqual(1.3, $this->progress->getEaseFactor());
    }

    public function testEaseFactorAdjustment(): void
    {
        $initialEF = $this->progress->getEaseFactor();
        
        // Good response increases EF
        $this->service->calculateNextReview($this->progress, 5);
        $efAfterGood = $this->progress->getEaseFactor();
        $this->assertGreaterThan($initialEF, $efAfterGood);
        
        // Poor response decreases EF
        $this->service->calculateNextReview($this->progress, 2);
        $efAfterPoor = $this->progress->getEaseFactor();
        $this->assertLessThan($efAfterGood, $efAfterPoor);
    }
}