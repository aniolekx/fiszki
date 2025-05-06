<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use App\Repository\DoctrineUserRepository;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\ORM\EntityManagerInterface;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Step\Then;

class LoginContext extends MinkContext implements Context
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
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $this->entityManager->flush();
    }

    /**
     * @Given a user exists with email :email and password :password
     */
    public function aUserExistsWithEmailAndPassword(string $email, string $password): void
    {
        $user = new User($email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
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
        // Note: The regex captures 'field' and 'value'. Type hinting removed for compatibility.
        $this->getSession()->getPage()->fillField("login[{$field}]", $value);
    }
}
