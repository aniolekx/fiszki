<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Flashcard;
use App\Entity\Deck;
use PHPUnit\Framework\TestCase;

class FlashcardTest extends TestCase
{
    private Flashcard $flashcard;

    protected function setUp(): void
    {
        $this->flashcard = new Flashcard();
    }

    public function testGettersAndSetters(): void
    {
        $this->assertNull($this->flashcard->getId());

        $front = 'What is the capital of France?';
        $this->flashcard->setFront($front);
        $this->assertEquals($front, $this->flashcard->getFront());

        $back = 'Paris';
        $this->flashcard->setBack($back);
        $this->assertEquals($back, $this->flashcard->getBack());
    }

    public function testDeckAssociation(): void
    {
        $this->assertNull($this->flashcard->getDeck());

        $deck = new Deck();
        $deck->setName('Geography');
        $this->flashcard->setDeck($deck);
        
        $this->assertSame($deck, $this->flashcard->getDeck());
    }

    public function testLongContent(): void
    {
        $longFront = str_repeat('This is a very long question. ', 100);
        $longBack = str_repeat('This is a very long answer. ', 100);

        $this->flashcard->setFront($longFront);
        $this->flashcard->setBack($longBack);

        $this->assertEquals($longFront, $this->flashcard->getFront());
        $this->assertEquals($longBack, $this->flashcard->getBack());
    }

    public function testEmptyContent(): void
    {
        $this->flashcard->setFront('');
        $this->flashcard->setBack('');

        $this->assertEquals('', $this->flashcard->getFront());
        $this->assertEquals('', $this->flashcard->getBack());
    }

    public function testSpecialCharacters(): void
    {
        $front = 'What is 2 + 2?';
        $back = '4 (2² = 4, √16 = 4)';

        $this->flashcard->setFront($front);
        $this->flashcard->setBack($back);

        $this->assertEquals($front, $this->flashcard->getFront());
        $this->assertEquals($back, $this->flashcard->getBack());
    }

    public function testUnicodeContent(): void
    {
        $front = '¿Cómo se dice "hello" en español?';
        $back = 'Hola 👋';

        $this->flashcard->setFront($front);
        $this->flashcard->setBack($back);

        $this->assertEquals($front, $this->flashcard->getFront());
        $this->assertEquals($back, $this->flashcard->getBack());
    }

    public function testHtmlContent(): void
    {
        $front = '<strong>What is HTML?</strong>';
        $back = '<p>HyperText Markup Language</p>';

        $this->flashcard->setFront($front);
        $this->flashcard->setBack($back);

        $this->assertEquals($front, $this->flashcard->getFront());
        $this->assertEquals($back, $this->flashcard->getBack());
    }

    public function testChangeDeck(): void
    {
        $deck1 = new Deck();
        $deck1->setName('Deck 1');
        $this->flashcard->setDeck($deck1);
        $this->assertSame($deck1, $this->flashcard->getDeck());

        $deck2 = new Deck();
        $deck2->setName('Deck 2');
        $this->flashcard->setDeck($deck2);
        $this->assertSame($deck2, $this->flashcard->getDeck());
    }

    public function testRemoveDeck(): void
    {
        $deck = new Deck();
        $deck->setName('Test Deck');
        $this->flashcard->setDeck($deck);
        $this->assertNotNull($this->flashcard->getDeck());

        $this->flashcard->setDeck(null);
        $this->assertNull($this->flashcard->getDeck());
    }

    public function testMultilineContent(): void
    {
        $front = "Line 1\nLine 2\nLine 3";
        $back = "Answer line 1\nAnswer line 2";

        $this->flashcard->setFront($front);
        $this->flashcard->setBack($back);

        $this->assertEquals($front, $this->flashcard->getFront());
        $this->assertEquals($back, $this->flashcard->getBack());
    }
}