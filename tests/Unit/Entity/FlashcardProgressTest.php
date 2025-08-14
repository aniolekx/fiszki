<?php

namespace App\Tests\Unit\Entity;

use App\Entity\FlashcardProgress;
use App\Entity\User;
use App\Entity\Flashcard;
use App\Entity\StudySession;
use PHPUnit\Framework\TestCase;

class FlashcardProgressTest extends TestCase
{
    private FlashcardProgress $progress;

    protected function setUp(): void
    {
        $this->progress = new FlashcardProgress();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->progress->getId());
        $this->assertNull($this->progress->getUser());
        $this->assertNull($this->progress->getFlashcard());
        $this->assertNull($this->progress->getStudySession());
        $this->assertEquals(0, $this->progress->getRepetitions());
        $this->assertEquals(2.5, $this->progress->getEaseFactor());
        $this->assertEquals(0, $this->progress->getInterval());
        $this->assertNotNull($this->progress->getLastReviewedAt());
        $this->assertNotNull($this->progress->getNextReviewAt());
        $this->assertEquals(0, $this->progress->getConsecutiveCorrect());
        $this->assertEquals(0, $this->progress->getTotalAttempts());
        $this->assertEquals(0, $this->progress->getCorrectAttempts());
        $this->assertNull($this->progress->getLastQuality());
    }

    public function testUserAssociation(): void
    {
        $user = new User('test@example.com');
        $this->progress->setUser($user);
        $this->assertSame($user, $this->progress->getUser());
    }

    public function testFlashcardAssociation(): void
    {
        $flashcard = new Flashcard();
        $flashcard->setFront('Question');
        $flashcard->setBack('Answer');
        $this->progress->setFlashcard($flashcard);
        $this->assertSame($flashcard, $this->progress->getFlashcard());
    }

    public function testStudySessionAssociation(): void
    {
        $session = new StudySession();
        $this->progress->setStudySession($session);
        $this->assertSame($session, $this->progress->getStudySession());
        
        $this->progress->setStudySession(null);
        $this->assertNull($this->progress->getStudySession());
    }

    public function testSettersAndGetters(): void
    {
        $lastReviewed = new \DateTime('2024-01-01');
        $nextReview = new \DateTime('2024-01-05');

        $this->progress->setRepetitions(5);
        $this->progress->setEaseFactor(2.8);
        $this->progress->setInterval(10);
        $this->progress->setLastReviewedAt($lastReviewed);
        $this->progress->setNextReviewAt($nextReview);
        $this->progress->setConsecutiveCorrect(3);
        $this->progress->setTotalAttempts(10);
        $this->progress->setCorrectAttempts(8);
        $this->progress->setLastQuality(4);

        $this->assertEquals(5, $this->progress->getRepetitions());
        $this->assertEquals(2.8, $this->progress->getEaseFactor());
        $this->assertEquals(10, $this->progress->getInterval());
        $this->assertEquals($lastReviewed, $this->progress->getLastReviewedAt());
        $this->assertEquals($nextReview, $this->progress->getNextReviewAt());
        $this->assertEquals(3, $this->progress->getConsecutiveCorrect());
        $this->assertEquals(10, $this->progress->getTotalAttempts());
        $this->assertEquals(8, $this->progress->getCorrectAttempts());
        $this->assertEquals(4, $this->progress->getLastQuality());
    }

    public function testIncrementTotalAttempts(): void
    {
        $this->assertEquals(0, $this->progress->getTotalAttempts());
        
        $this->progress->incrementTotalAttempts();
        $this->assertEquals(1, $this->progress->getTotalAttempts());
        
        $this->progress->incrementTotalAttempts();
        $this->progress->incrementTotalAttempts();
        $this->assertEquals(3, $this->progress->getTotalAttempts());
    }

    public function testIncrementCorrectAttempts(): void
    {
        $this->assertEquals(0, $this->progress->getCorrectAttempts());
        
        $this->progress->incrementCorrectAttempts();
        $this->assertEquals(1, $this->progress->getCorrectAttempts());
        
        $this->progress->incrementCorrectAttempts();
        $this->assertEquals(2, $this->progress->getCorrectAttempts());
    }

    public function testAccuracyCalculation(): void
    {
        $this->assertEquals(0, $this->progress->getAccuracy());
        
        $this->progress->setTotalAttempts(10);
        $this->progress->setCorrectAttempts(8);
        $this->assertEquals(80.0, $this->progress->getAccuracy());
        
        $this->progress->setTotalAttempts(3);
        $this->progress->setCorrectAttempts(2);
        $this->assertEquals(66.67, $this->progress->getAccuracy());
        
        $this->progress->setTotalAttempts(100);
        $this->progress->setCorrectAttempts(100);
        $this->assertEquals(100.0, $this->progress->getAccuracy());
    }

    public function testIsDue(): void
    {
        $now = new \DateTime();
        
        // Card due yesterday
        $yesterday = (clone $now)->modify('-1 day');
        $this->progress->setNextReviewAt($yesterday);
        $this->assertTrue($this->progress->isDue());
        
        // Card due tomorrow
        $tomorrow = (clone $now)->modify('+1 day');
        $this->progress->setNextReviewAt($tomorrow);
        $this->assertFalse($this->progress->isDue());
        
        // Card due now
        $this->progress->setNextReviewAt($now);
        $this->assertTrue($this->progress->isDue());
    }

    public function testEaseFactorBounds(): void
    {
        $this->progress->setEaseFactor(1.3);
        $this->assertEquals(1.3, $this->progress->getEaseFactor());
        
        $this->progress->setEaseFactor(5.0);
        $this->assertEquals(5.0, $this->progress->getEaseFactor());
    }

    public function testQualityValues(): void
    {
        $validQualities = [0, 1, 2, 3, 4, 5];
        
        foreach ($validQualities as $quality) {
            $this->progress->setLastQuality($quality);
            $this->assertEquals($quality, $this->progress->getLastQuality());
        }
        
        $this->progress->setLastQuality(null);
        $this->assertNull($this->progress->getLastQuality());
    }

    public function testProgressAfterMultipleReviews(): void
    {
        $this->progress->setRepetitions(10);
        $this->progress->setEaseFactor(3.0);
        $this->progress->setInterval(30);
        $this->progress->setConsecutiveCorrect(8);
        $this->progress->setTotalAttempts(15);
        $this->progress->setCorrectAttempts(12);
        
        $this->assertEquals(80.0, $this->progress->getAccuracy());
        $this->assertEquals(30, $this->progress->getInterval());
        $this->assertEquals(3.0, $this->progress->getEaseFactor());
    }
}