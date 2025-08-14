<?php

namespace App\Tests\Behat\Context;

use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Entity\FlashcardProgress;
use App\Entity\StudySession;
use App\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Tester\Exception\PendingException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use PHPUnit\Framework\Assert;

class StudySessionContext extends MinkContext implements Context
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ?User $currentUser = null;
    private ?Deck $currentDeck = null;

    public function __construct(
        KernelInterface $kernel,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @BeforeScenario
     */
    public function cleanDatabase(BeforeScenarioScope $scope): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE flashcard_progress');
        $connection->executeStatement('TRUNCATE TABLE study_session');
        $connection->executeStatement('TRUNCATE TABLE flashcard');
        $connection->executeStatement('TRUNCATE TABLE deck');
        $connection->executeStatement('TRUNCATE TABLE users');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @Given a user exists with email :email and password :password
     */
    public function aUserExistsWithEmailAndPassword(string $email, string $password): void
    {
        $user = new User($email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setIsConfirmed(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $this->currentUser = $user;
    }

    /**
     * @Given I fill in the login field :field with :value
     */
    public function iFillInTheLoginFieldWith(string $field, string $value): void
    {
        $this->fillField("login[$field]", $value);
    }


    /**
     * @Then I should be redirected to the :url page
     */
    public function iShouldBeRedirectedToThePage(string $url): void
    {
        $this->assertSession()->addressEquals($url);
    }

    /**
     * @Given there is a deck named :name with :count flashcards
     */
    public function thereIsADeckNamedWithFlashcards(string $name, int $count): void
    {
        $deck = new Deck();
        $deck->setName($name);
        $deck->setDescription('Test deck for study session');
        $deck->setUser($this->currentUser);
        $this->entityManager->persist($deck);
        
        for ($i = 1; $i <= $count; $i++) {
            $flashcard = new Flashcard();
            $flashcard->setFront("Question $i");
            $flashcard->setBack("Answer $i");
            $flashcard->setDeck($deck);
            $this->entityManager->persist($flashcard);
        }
        
        $this->entityManager->flush();
        $this->currentDeck = $deck;
    }

    /**
     * @When I click :text
     */
    public function iClick(string $text): void
    {
        $this->clickLink($text);
    }

    /**
     * @Given I have started a study session for :deckName
     */
    public function iHaveStartedAStudySessionFor(string $deckName): void
    {
        $this->visit('/study/');
        $this->clickLink('Rozpocznij naukę');
    }

    /**
     * @When I click the flashcard
     */
    public function iClickTheFlashcard(): void
    {
        $this->getSession()->executeScript("document.getElementById('flashcard').click();");
    }

    /**
     * @When I rate the card with quality :quality
     */
    public function iRateTheCardWithQuality(int $quality): void
    {
        $this->getSession()->executeScript("
            document.querySelector('[data-quality=\"$quality\"]').click();
        ");
    }

    /**
     * @Then I should be redirected to the next card
     */
    public function iShouldBeRedirectedToTheNextCard(): void
    {
        $this->getSession()->wait(2000, "window.location.pathname === '/study/card'");
        $this->assertSession()->addressEquals('/study/card');
    }

    /**
     * @Given I have reviewed all cards in my study session
     */
    public function iHaveReviewedAllCardsInMyStudySession(): void
    {
        $session = new StudySession();
        $session->setUser($this->currentUser);
        $session->setDeck($this->currentDeck);
        $session->setTotalCards(3);
        $session->setReviewedCards(3);
        $session->setCorrectAnswers(3);
        $session->setStatus('completed');
        $session->setCompletedAt(new \DateTime());
        
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        
        $this->getSession()->setCookie('study_session_id', $session->getId());
    }

    /**
     * @Then I should see a :text button
     */
    public function iShouldSeeAButton(string $text): void
    {
        $this->assertSession()->elementTextContains('css', 'a.btn, button', $text);
    }

    /**
     * @Given I have completed :count study sessions
     */
    public function iHaveCompletedStudySessions(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $session = new StudySession();
            $session->setUser($this->currentUser);
            $session->setDeck($this->currentDeck);
            $session->setTotalCards(10);
            $session->setReviewedCards(10);
            $session->setCorrectAnswers(8);
            $session->setStatus('completed');
            $session->setStartedAt((new \DateTime())->modify("-$i days"));
            $session->setCompletedAt((new \DateTime())->modify("-$i days +30 minutes"));
            
            $this->entityManager->persist($session);
        }
        
        $this->entityManager->flush();
    }

    /**
     * @Then I should see :count sessions in the history table
     */
    public function iShouldSeeSessionsInTheHistoryTable(int $count): void
    {
        $rows = $this->getSession()->getPage()->findAll('css', 'tbody tr');
        Assert::assertCount($count, $rows, "Expected $count sessions in the history table");
    }

    /**
     * @Given all my flashcards are scheduled for tomorrow
     */
    public function allMyFlashcardsAreScheduledForTomorrow(): void
    {
        $tomorrow = (new \DateTime())->modify('+1 day');
        
        foreach ($this->currentDeck->getFlashcards() as $flashcard) {
            $progress = new FlashcardProgress();
            $progress->setUser($this->currentUser);
            $progress->setFlashcard($flashcard);
            $progress->setNextReviewAt($tomorrow);
            $progress->setInterval(1);
            $progress->setRepetitions(1);
            
            $this->entityManager->persist($progress);
        }
        
        $this->entityManager->flush();
    }

    /**
     * @When I click :text for :deckName
     */
    public function iClickForDeck(string $text, string $deckName): void
    {
        $deckCard = $this->getSession()->getPage()->find('xpath', 
            "//h5[contains(text(), '$deckName')]/ancestor::div[@class='card']"
        );
        
        if (!$deckCard) {
            throw new \Exception("Could not find deck card for: $deckName");
        }
        
        $button = $deckCard->find('xpath', ".//a[contains(text(), '$text')]");
        
        if (!$button) {
            throw new \Exception("Could not find button '$text' in deck card");
        }
        
        $button->click();
    }
}