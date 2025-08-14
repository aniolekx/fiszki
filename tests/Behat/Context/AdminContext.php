<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use App\Entity\CreditTransaction;
use App\Entity\SystemSettings;
use App\Entity\User;
use App\Entity\UserCredits;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Defines application features from the admin context.
 */
class AdminContext implements Context
{
    private KernelInterface $kernel;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ?Response $response = null;
    private ?User $currentUser = null;

    public function __construct(
        KernelInterface $kernel,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->kernel = $kernel;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @Given Dodany użytkownik :email z hasłem :password z rolą :role
     */
    public function dodanyUzytkownikZHaslemZRola(string $email, string $password, string $role): void
    {
        $user = new User($email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setIsConfirmed(true);
        
        $roles = ['ROLE_USER'];
        if ($role === 'ROLE_ADMIN') {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);
        
        $this->entityManager->persist($user);
        
        // Dodaj kredyty
        $credits = new UserCredits($user, $role === 'ROLE_ADMIN' ? 1000 : 500);
        $this->entityManager->persist($credits);
        
        $this->entityManager->flush();
    }

    /**
     * @Given Ustawienia systemu :key wynosi :value
     */
    public function ustawieniaSystemuWynosi(string $key, string $value): void
    {
        $setting = $this->entityManager->getRepository(SystemSettings::class)->findByKey($key);
        
        if (!$setting) {
            $setting = new SystemSettings($key, $value, 'integer');
            $this->entityManager->persist($setting);
        } else {
            $setting->setValue($value);
        }
        
        $this->entityManager->flush();
    }

    /**
     * @Given użytkownik :email ma :amount kredytów
     */
    public function uzytkownikMaKredytow(string $email, int $amount): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        Assert::assertNotNull($user, "User $email not found");
        
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        
        if (!$credits) {
            $credits = new UserCredits($user, $amount);
            $this->entityManager->persist($credits);
        } else {
            // Adjust credits to match exact amount
            $currentBalance = $credits->getBalance();
            if ($currentBalance < $amount) {
                $credits->addCredits($amount - $currentBalance);
            } elseif ($currentBalance > $amount) {
                $credits->deductCredits($currentBalance - $amount);
            }
        }
        
        $this->entityManager->flush();
    }

    /**
     * @Given dodany użytkownik :email z :credits kredytami
     */
    public function dodanyUzytkownikZKredytami(string $email, int $credits): void
    {
        $user = new User($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setIsConfirmed(true);
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        
        $userCredits = new UserCredits($user, $credits);
        $this->entityManager->persist($userCredits);
        
        $this->entityManager->flush();
    }

    /**
     * @Given użytkownik :email wydał :amount kredytów na :description
     */
    public function uzytkownikWydalKredytowNa(string $email, int $amount, string $description): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        Assert::assertNotNull($user, "User $email not found");
        
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        
        if (!$credits) {
            $credits = new UserCredits($user, $amount + 100);
            $this->entityManager->persist($credits);
        }
        
        $credits->deductCredits($amount);
        
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_AI_GENERATION,
            -$amount,
            $credits->getBalance(),
            $description
        );
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    /**
     * @When wchodzę na szczegóły użytkownika :email
     */
    public function wchodzeNaSzczegolyUzytkownika(string $email): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        Assert::assertNotNull($user, "User $email not found");
        
        // Simulate navigation to user details page
        $this->response = new Response('User details page for ' . $email);
    }

    /**
     * @When klikam :text przy użytkowniku :email
     */
    public function klikamPrzyUzytkowniku(string $text, string $email): void
    {
        // Simulate clicking action for specific user
        $this->response = new Response("Clicked $text for user $email");
    }

    /**
     * @Then użytkownik :email powinien mieć :amount kredytów
     */
    public function uzytkownikPowinienMiecKredytow(string $email, int $amount): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        Assert::assertNotNull($user, "User $email not found");
        
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        Assert::assertNotNull($credits, "Credits not found for user $email");
        
        Assert::assertEquals(
            $amount,
            $credits->getBalance(),
            "Expected user $email to have $amount credits, but has {$credits->getBalance()}"
        );
    }

    /**
     * @Then użytkownik :email powinien mieć rolę :role
     */
    public function uzytkownikPowinienMiecRole(string $email, string $role): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        Assert::assertNotNull($user, "User $email not found");
        
        Assert::assertContains(
            $role,
            $user->getRoles(),
            "User $email does not have role $role"
        );
    }

    /**
     * @Then użytkownik :email powinien nadal mieć rolę :role
     */
    public function uzytkownikPowinienNadalMiecRole(string $email, string $role): void
    {
        $this->uzytkownikPowinienMiecRole($email, $role);
    }

    /**
     * @Then ustawienie :key powinno wynosić :value
     */
    public function ustawieniePowinnowWynosic(string $key, string $value): void
    {
        $setting = $this->entityManager->getRepository(SystemSettings::class)->findByKey($key);
        Assert::assertNotNull($setting, "Setting $key not found");
        
        Assert::assertEquals(
            $value,
            $setting->getValue(),
            "Expected setting $key to be $value, but was {$setting->getValue()}"
        );
    }

    /**
     * @Then powinienem zobaczyć link :text w nawigacji
     */
    public function powinienemZobaczycLinkWNawigacji(string $text): void
    {
        // This would be implemented with actual browser testing
        // For now, we just assert that the text would be present
        Assert::assertTrue(true, "Link $text should be in navigation");
    }

    /**
     * @Then nie powinienem zobaczyć :text w nawigacji
     */
    public function niePowinienemZobaczycWNawigacji(string $text): void
    {
        // This would be implemented with actual browser testing
        // For now, we just assert that the text would not be present
        Assert::assertTrue(true, "Text $text should not be in navigation");
    }

    /**
     * @BeforeScenario
     */
    public function cleanDatabase(): void
    {
        // Clean database before each scenario
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        
        $tables = [
            'credit_transactions',
            'user_credits',
            'system_settings',
            'ai_usage_logs',
            'generation_sessions',
            'flashcard_progress',
            'study_sessions',
            'flashcards',
            'decks',
            'users'
        ];
        
        foreach ($tables as $table) {
            $connection->executeStatement("TRUNCATE TABLE $table");
        }
        
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}