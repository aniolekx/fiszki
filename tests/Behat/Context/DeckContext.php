<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Entity\User;
use App\Repository\DoctrineUserRepository;
use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DeckContext implements Context
{
    private MinkContext $minkContext;

    public function __construct(
        private readonly DoctrineUserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel
    ) {
        $this->minkContext = new MinkContext();
    }

    /**
     * @Given there is a deck named :name
     */
    public function thereIsADeckNamed(string $name): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'aniolekx@gmail.com']);
        
        $deck = new Deck();
        $deck->setName($name);
        $deck->setDescription('Test description');
        $deck->setUser($user);

        $this->entityManager->persist($deck);
        $this->entityManager->flush();
    }

    /**
     * @When I fill in :field with :value
     */
    public function iFillInWith(string $field, string $value): void
    {
        $this->minkContext->getSession()->getPage()->fillField($field, $value);
    }

    /**
     * @When I press :button
     */
    public function iPress(string $button): void
    {
        $this->minkContext->pressButton($button);
    }

    /**
     * @Then I should see :text
     */
    public function iShouldSee(string $text): void
    {
        $this->minkContext->assertSession()->pageTextContains($text);
    }

    /**
     * @Then I should see the error message :message
     */
    public function iShouldSeeTheErrorMessage(string $message): void
    {
        $this->minkContext->assertSession()->elementTextContains('css', '.alert-danger', $message);
    }

    /**
     * @Then I should be redirected to the :path page
     */
    public function iShouldBeRedirectedToThePage(string $path): void
    {
        $this->minkContext->assertSession()->addressEquals($this->minkContext->locatePath($path));
    }

    /**
     * @Then I should still be on the :path page
     */
    public function iShouldStillBeOnThePage(string $path): void
    {
        $this->minkContext->assertSession()->addressEquals($this->minkContext->locatePath($path));
    }
} 