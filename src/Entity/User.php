<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DoctrineUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: DoctrineUserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 180, unique: true)]
    private string $email;
    
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Deck::class, orphanRemoval: true)]
    private Collection $decks;
    
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserCredits::class, cascade: ['persist', 'remove'])]
    private ?UserCredits $credits = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isConfirmed = false;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->decks = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): self
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
    
    public function getCredits(): ?UserCredits
    {
        return $this->credits;
    }
    
    public function setCredits(UserCredits $credits): self
    {
        $this->credits = $credits;
        if ($credits->getUser() !== $this) {
            $credits->setUser($this);
        }
        return $this;
    }
    
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }
    
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
