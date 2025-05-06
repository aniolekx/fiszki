<?php

namespace App\Entity;

use App\Repository\FlashcardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlashcardRepository::class)]
class Flashcard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $front = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $back = '';

    #[ORM\ManyToOne(targetEntity: Deck::class, inversedBy: 'flashcards')]
    private ?Deck $deck = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFront(): string
    {
        return $this->front;
    }

    public function setFront(string $front): self
    {
        $this->front = $front;

        return $this;
    }

    public function getBack(): string
    {
        return $this->back;
    }

    public function setBack(string $back): self
    {
        $this->back = $back;

        return $this;
    }

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): self
    {
        $this->deck = $deck;

        return $this;
    }
}
