<?php

namespace App\Tests\Unit\Entity;

use App\Entity\StudySession;
use App\Entity\User;
use App\Entity\Deck;
use App\Entity\FlashcardProgress;
use PHPUnit\Framework\TestCase;

class StudySessionTest extends TestCase
{
    private StudySession $session;

    protected function setUp(): void
    {
        $this->session = new StudySession();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->session->getId());
        $this->assertNull($this->session->getUser());
        $this->assertNull($this->session->getDeck());
        $this->assertNull($this->session->getCompletedAt());
        $this->assertNotNull($this->session->getStartedAt());
        $this->assertEquals(0, $this->session->getTotalCards());
        $this->assertEquals(0, $this->session->getReviewedCards());
        $this->assertEquals(0, $this->session->getCorrectAnswers());
        $this->assertEquals('in_progress', $this->session->getStatus());
    }

    public function testUserAssociation(): void
    {
        $user = new User('test@example.com');
        $this->session->setUser($user);
        $this->assertSame($user, $this->session->getUser());
    }

    public function testDeckAssociation(): void
    {
        $deck = new Deck();
        $deck->setName('Test Deck');
        $this->session->setDeck($deck);
        $this->assertSame($deck, $this->session->getDeck());
    }

    public function testSettersAndGetters(): void
    {
        $startedAt = new \DateTime('2024-01-01 10:00:00');
        $completedAt = new \DateTime('2024-01-01 10:30:00');

        $this->session->setStartedAt($startedAt);
        $this->session->setCompletedAt($completedAt);
        $this->session->setTotalCards(20);
        $this->session->setReviewedCards(15);
        $this->session->setCorrectAnswers(12);
        $this->session->setStatus('completed');

        $this->assertEquals($startedAt, $this->session->getStartedAt());
        $this->assertEquals($completedAt, $this->session->getCompletedAt());
        $this->assertEquals(20, $this->session->getTotalCards());
        $this->assertEquals(15, $this->session->getReviewedCards());
        $this->assertEquals(12, $this->session->getCorrectAnswers());
        $this->assertEquals('completed', $this->session->getStatus());
    }

    public function testIncrementReviewedCards(): void
    {
        $this->session->setReviewedCards(5);
        $this->session->incrementReviewedCards();
        $this->assertEquals(6, $this->session->getReviewedCards());
        
        $this->session->incrementReviewedCards();
        $this->session->incrementReviewedCards();
        $this->assertEquals(8, $this->session->getReviewedCards());
    }

    public function testIncrementCorrectAnswers(): void
    {
        $this->session->setCorrectAnswers(3);
        $this->session->incrementCorrectAnswers();
        $this->assertEquals(4, $this->session->getCorrectAnswers());
    }

    public function testAccuracyCalculation(): void
    {
        $this->session->setReviewedCards(10);
        $this->session->setCorrectAnswers(8);
        $this->assertEquals(80.0, $this->session->getAccuracy());

        $this->session->setReviewedCards(3);
        $this->session->setCorrectAnswers(2);
        $this->assertEquals(66.67, $this->session->getAccuracy());

        $this->session->setReviewedCards(0);
        $this->session->setCorrectAnswers(0);
        $this->assertEquals(0, $this->session->getAccuracy());
    }

    public function testFlashcardProgressCollection(): void
    {
        $this->assertCount(0, $this->session->getFlashcardProgresses());

        $progress1 = new FlashcardProgress();
        $progress2 = new FlashcardProgress();

        $this->session->addFlashcardProgress($progress1);
        $this->assertCount(1, $this->session->getFlashcardProgresses());
        $this->assertTrue($this->session->getFlashcardProgresses()->contains($progress1));
        $this->assertSame($this->session, $progress1->getStudySession());

        $this->session->addFlashcardProgress($progress2);
        $this->assertCount(2, $this->session->getFlashcardProgresses());

        // Test adding same progress twice
        $this->session->addFlashcardProgress($progress1);
        $this->assertCount(2, $this->session->getFlashcardProgresses());

        $this->session->removeFlashcardProgress($progress1);
        $this->assertCount(1, $this->session->getFlashcardProgresses());
        $this->assertFalse($this->session->getFlashcardProgresses()->contains($progress1));
        $this->assertNull($progress1->getStudySession());
    }

    public function testStatusValues(): void
    {
        $validStatuses = ['in_progress', 'completed', 'abandoned'];
        
        foreach ($validStatuses as $status) {
            $this->session->setStatus($status);
            $this->assertEquals($status, $this->session->getStatus());
        }
    }

    public function testPerfectSession(): void
    {
        $this->session->setTotalCards(10);
        $this->session->setReviewedCards(10);
        $this->session->setCorrectAnswers(10);
        
        $this->assertEquals(100.0, $this->session->getAccuracy());
    }

    public function testPartialSession(): void
    {
        $this->session->setTotalCards(20);
        $this->session->setReviewedCards(10);
        $this->session->setCorrectAnswers(7);
        $this->session->setStatus('abandoned');
        
        $this->assertEquals(70.0, $this->session->getAccuracy());
        $this->assertEquals('abandoned', $this->session->getStatus());
    }
}