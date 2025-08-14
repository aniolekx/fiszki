<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Entity\StudySession;
use App\Entity\FlashcardProgress;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StudyControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;
    private $deck;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->cleanDatabase();
        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
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

    private function setupTestData(): void
    {
        // Create user
        $this->user = new User('test@example.com');
        $hashedPassword = $this->client->getContainer()
            ->get('security.user_password_hasher')
            ->hashPassword($this->user, 'Test123!');
        $this->user->setPassword($hashedPassword);
        $this->user->setIsConfirmed(true);
        $this->entityManager->persist($this->user);

        // Create deck with flashcards
        $this->deck = new Deck();
        $this->deck->setName('Test Deck');
        $this->deck->setDescription('Deck for testing');
        $this->deck->setUser($this->user);
        $this->entityManager->persist($this->deck);

        // Create flashcards
        for ($i = 1; $i <= 5; $i++) {
            $flashcard = new Flashcard();
            $flashcard->setFront("Question $i");
            $flashcard->setBack("Answer $i");
            $flashcard->setDeck($this->deck);
            $this->entityManager->persist($flashcard);
        }

        $this->entityManager->flush();
    }

    private function login(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'login[email]' => 'test@example.com',
            'login[password]' => 'Test123!',
        ]);
        $this->client->submit($form);
    }

    public function testStudyIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/study/');
        $this->assertResponseRedirects('/login');
    }

    public function testStudyIndexShowsDecksWithStats(): void
    {
        $this->login();
        
        $crawler = $this->client->request('GET', '/study/');
        $this->assertResponseIsSuccessful();
        
        $this->assertSelectorTextContains('h1', 'Sesja nauki');
        $this->assertSelectorTextContains('.card-title', 'Test Deck');
        $this->assertSelectorExists('.btn:contains("Rozpocznij naukę")');
        
        // Check stats are displayed
        $this->assertSelectorTextContains('.badge.bg-secondary', '5'); // Total cards
        $this->assertSelectorTextContains('.badge.bg-success', '5'); // New cards
    }

    public function testStartStudySession(): void
    {
        $this->login();
        
        $this->client->request('GET', '/study/deck/' . $this->deck->getId() . '/start');
        $this->assertResponseRedirects('/study/card');
        
        // Verify session was created
        $session = $this->entityManager->getRepository(StudySession::class)
            ->findOneBy(['user' => $this->user, 'deck' => $this->deck]);
        
        $this->assertNotNull($session);
        $this->assertEquals('in_progress', $session->getStatus());
        $this->assertEquals(5, $session->getTotalCards());
    }

    public function testCannotStartSessionForOtherUsersDeck(): void
    {
        // Create another user with a deck
        $otherUser = new User('other@example.com');
        $otherUser->setPassword('password');
        $otherUser->setIsConfirmed(true);
        $this->entityManager->persist($otherUser);
        
        $otherDeck = new Deck();
        $otherDeck->setName('Other Deck');
        $otherDeck->setUser($otherUser);
        $this->entityManager->persist($otherDeck);
        $this->entityManager->flush();
        
        $this->login();
        
        $this->client->request('GET', '/study/deck/' . $otherDeck->getId() . '/start');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testStudyCardDisplay(): void
    {
        $this->login();
        
        // Start session
        $this->client->request('GET', '/study/deck/' . $this->deck->getId() . '/start');
        
        // View card
        $crawler = $this->client->request('GET', '/study/card');
        $this->assertResponseIsSuccessful();
        
        $this->assertSelectorExists('#flashcard');
        $this->assertSelectorTextContains('.flashcard-front', 'Pytanie');
        $this->assertSelectorExists('#show-answer-btn');
        $this->assertSelectorExists('#rating-section');
    }

    public function testAnswerCard(): void
    {
        $this->login();
        
        // Start session
        $this->client->request('GET', '/study/deck/' . $this->deck->getId() . '/start');
        
        // Answer card
        $this->client->request('POST', '/study/card/answer', [
            'quality' => 4
        ]);
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertTrue($response['hasNext']);
        $this->assertStringContainsString('/study/card', $response['nextUrl']);
        
        // Check progress was updated
        $progress = $this->entityManager->getRepository(FlashcardProgress::class)
            ->findOneBy(['user' => $this->user]);
        
        $this->assertNotNull($progress);
        $this->assertEquals(1, $progress->getTotalAttempts());
        $this->assertEquals(1, $progress->getCorrectAttempts());
        $this->assertEquals(4, $progress->getLastQuality());
    }

    public function testCompleteSession(): void
    {
        $this->login();
        
        // Create a session
        $session = new StudySession();
        $session->setUser($this->user);
        $session->setDeck($this->deck);
        $session->setTotalCards(5);
        $session->setReviewedCards(5);
        $session->setCorrectAnswers(4);
        $session->setStatus('in_progress');
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        
        // Store session in session data
        $this->client->getContainer()->get('session')->set('study_session_id', $session->getId());
        
        $crawler = $this->client->request('GET', '/study/complete');
        $this->assertResponseIsSuccessful();
        
        $this->assertSelectorTextContains('h2', 'Gratulacje!');
        $this->assertSelectorTextContains('.h5', 'Test Deck');
        $this->assertSelectorTextContains('.progress-bar', '80%');
        
        // Verify session was completed
        $this->entityManager->refresh($session);
        $this->assertEquals('completed', $session->getStatus());
        $this->assertNotNull($session->getCompletedAt());
    }

    public function testStudyHistory(): void
    {
        $this->login();
        
        // Create some sessions
        for ($i = 0; $i < 3; $i++) {
            $session = new StudySession();
            $session->setUser($this->user);
            $session->setDeck($this->deck);
            $session->setTotalCards(10);
            $session->setReviewedCards(10 - $i);
            $session->setCorrectAnswers(8 - $i);
            $session->setStatus($i === 0 ? 'completed' : 'in_progress');
            $session->setStartedAt((new \DateTime())->modify("-$i days"));
            $this->entityManager->persist($session);
        }
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/study/history');
        $this->assertResponseIsSuccessful();
        
        $this->assertSelectorTextContains('h1', 'Historia sesji nauki');
        $this->assertCount(3, $crawler->filter('tbody tr'));
        
        // Check statistics
        $this->assertSelectorTextContains('.card-text.display-6', '3'); // Total sessions
    }

    public function testNoDueCardsRedirect(): void
    {
        $this->login();
        
        // Create all cards as already reviewed
        foreach ($this->deck->getFlashcards() as $flashcard) {
            $progress = new FlashcardProgress();
            $progress->setUser($this->user);
            $progress->setFlashcard($flashcard);
            $progress->setNextReviewAt((new \DateTime())->modify('+1 day'));
            $this->entityManager->persist($progress);
        }
        $this->entityManager->flush();
        
        $this->client->request('GET', '/study/deck/' . $this->deck->getId() . '/start');
        $this->assertResponseRedirects('/study/');
        
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert', 'Nie masz żadnych fiszek do powtórki');
    }

    public function testInvalidQualityAnswer(): void
    {
        $this->login();
        
        // Start session
        $this->client->request('GET', '/study/deck/' . $this->deck->getId() . '/start');
        
        // Send invalid quality
        $this->client->request('POST', '/study/card/answer', [
            'quality' => 10
        ]);
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }
}