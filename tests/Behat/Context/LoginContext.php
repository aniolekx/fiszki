<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Session;
use App\Repository\DoctrineUserRepository;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Step\Then;
use Doctrine\ORM\EntityManagerInterface;

class LoginContext extends RawMinkContext implements Context
{
    public function __construct(
        private readonly DoctrineUserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @BeforeScenario
     */
    public function clearDatabase(BeforeScenarioScope $scope): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE users');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        $this->entityManager->clear();
    }

    /**
     * @Given a user exists with email :email and password :password
     */
    public function aUserExistsWithEmailAndPassword(string $email, string $password): void
    {
        $user = new User($email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setConfirmationToken(null);
        $user->setIsConfirmed(true);
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @Given I am on the :path page
     */
    public function iAmOnThePage(string $path): void
    {
        $this->visitPath($path);
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
     * @Then I should see the error message :message
     */
    public function iShouldSeeTheErrorMessage(string $message): void
    {
        $this->assertSession()->elementTextContains('css', '.alert-danger', $message);
    }

    /**
     * @When /^(?:|I )fill in the login field "(?P<field>[^"]*)" with "(?P<value>[^"]*)"$/
     */
    public function fillField($field, $value): void
    {
        $this->getSession()->getPage()->fillField("login[{$field}]", $value);
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
     * @When I follow :link
     */
    public function iFollow(string $link): void
    {
        $this->getSession()->getPage()->clickLink($link);
    }
}
