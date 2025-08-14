<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Deck;
use App\Entity\User;
use App\Entity\Flashcard;
use PHPUnit\Framework\TestCase;

class DeckTest extends TestCase
{
    private Deck $deck;

    protected function setUp(): void
    {
        $this->deck = new Deck();
    }

    public function testGettersAndSetters(): void
    {
        $this->assertNull($this->deck->getId());

        $name = 'English Vocabulary';
        $this->deck->setName($name);
        $this->assertEquals($name, $this->deck->getName());

        $description = 'Deck for learning English words';
        $this->deck->setDescription($description);
        $this->assertEquals($description, $this->deck->getDescription());
    }

    public function testUserAssociation(): void
    {
        $this->assertNull($this->deck->getUser());

        $user = new User('test@example.com');
        $this->deck->setUser($user);
        
        $this->assertSame($user, $this->deck->getUser());
    }

    public function testFlashcardsCollection(): void
    {
        $this->assertCount(0, $this->deck->getFlashcards());

        $flashcard1 = new Flashcard();
        $flashcard1->setFront('Question 1');
        $flashcard1->setBack('Answer 1');
        $this->deck->addFlashcard($flashcard1);
        
        $this->assertCount(1, $this->deck->getFlashcards());
        $this->assertTrue($this->deck->getFlashcards()->contains($flashcard1));
        $this->assertSame($this->deck, $flashcard1->getDeck());

        $flashcard2 = new Flashcard();
        $flashcard2->setFront('Question 2');
        $flashcard2->setBack('Answer 2');
        $this->deck->addFlashcard($flashcard2);
        
        $this->assertCount(2, $this->deck->getFlashcards());

        $this->deck->removeFlashcard($flashcard1);
        $this->assertCount(1, $this->deck->getFlashcards());
        $this->assertFalse($this->deck->getFlashcards()->contains($flashcard1));
        $this->assertNull($flashcard1->getDeck());
    }

    public function testAddSameFlashcardTwice(): void
    {
        $flashcard = new Flashcard();
        $flashcard->setFront('Question');
        $flashcard->setBack('Answer');

        $this->deck->addFlashcard($flashcard);
        $this->deck->addFlashcard($flashcard);

        $this->assertCount(1, $this->deck->getFlashcards());
    }

    public function testRemoveNonExistentFlashcard(): void
    {
        $flashcard = new Flashcard();
        $flashcard->setFront('Question');
        $flashcard->setBack('Answer');

        $this->deck->removeFlashcard($flashcard);
        $this->assertCount(0, $this->deck->getFlashcards());
    }

    public function testDeckWithMultipleFlashcards(): void
    {
        $user = new User('learner@example.com');
        $this->deck->setUser($user);
        $this->deck->setName('Math Formulas');
        $this->deck->setDescription('Important mathematical formulas');

        for ($i = 1; $i <= 5; $i++) {
            $flashcard = new Flashcard();
            $flashcard->setFront("Formula $i");
            $flashcard->setBack("Solution $i");
            $this->deck->addFlashcard($flashcard);
        }

        $this->assertCount(5, $this->deck->getFlashcards());
        $this->assertEquals('Math Formulas', $this->deck->getName());
        $this->assertEquals('learner@example.com', $this->deck->getUser()->getEmail());
    }

    public function testEmptyDescription(): void
    {
        $this->deck->setName('Test Deck');
        $this->deck->setDescription(null);
        
        $this->assertNull($this->deck->getDescription());
    }
}