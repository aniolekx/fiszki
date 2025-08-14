<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Entity\User;
use App\Repository\DoctrineUserRepository;
use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Step\Given;
use Behat\Step\When;

class DeckContext extends RawMinkContext
{
    public function __construct(
        private readonly DoctrineUserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel
    ) {
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
        $this->getSession()->getPage()->fillField($field, $value);
    }

    /**
     * @When I press :button
     */
    public function iPress(string $button): void
    {
        $this->getSession()->getPage()->pressButton($button);
    }

    /**
     * @Then I should see :text
     */
    public function iShouldSee(string $text): void
    {
        $this->assertSession()->pageTextContains($text);
    }

    /**
     * @Then I should see the error message :message
     */
    public function iShouldSeeTheErrorMessage(string $message): void
    {
        // Try multiple selectors for error messages
        $errorSelectors = ['.alert-danger', '.invalid-feedback', '.text-danger', '.error'];
        $found = false;
        
        foreach ($errorSelectors as $selector) {
            try {
                $this->assertSession()->elementTextContains('css', $selector, $message);
                $found = true;
                break;
            } catch (\Exception $e) {
                // Continue to next selector
            }
        }
        
        if (!$found) {
            // If not found in specific error elements, check page text
            $this->assertSession()->pageTextContains($message);
        }
    }

    /**
     * @Then I should be redirected to the :path page
     */
    public function iShouldBeRedirectedToThePage(string $path): void
    {
        $this->assertSession()->addressEquals($this->locatePath($path));
    }

    /**
     * @Then I should still be on the :path page
     */
    public function iShouldStillBeOnThePage(string $path): void
    {
        $this->assertSession()->addressEquals($this->locatePath($path));
    }

    /**
     * @Given a user exists with email :email and password :password
     */
    public function aUserExistsWithEmailAndPassword(string $email, string $password): void
    {
        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return;
        }

        $user = new User($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsConfirmed(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @Given I am on the :path page
     */
    public function iAmOnThePage(string $path): void
    {
        $this->getSession()->visit($this->locatePath($path));
    }

    /**
     * @When I fill in the login field :field with :value
     */
    public function iFillInTheLoginFieldWith(string $field, string $value): void
    {
        // Map field names to actual form field IDs
        $fieldMap = [
            'email' => 'login_email',
            'password' => 'login_password',
        ];

        $actualField = $fieldMap[$field] ?? $field;
        $this->getSession()->getPage()->fillField($actualField, $value);
    }
} 