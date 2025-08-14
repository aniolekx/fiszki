<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Deck;
use App\Entity\SystemSettings;
use App\Entity\User;
use App\Entity\UserCredits;
use App\Exception\OpenAIException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AIGeneratorErrorHandlingTest extends WebTestCase
{
    private $client;
    private $entityManager;
    
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        
        $this->clearDatabase();
        $this->createDefaultSettings();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
    
    private function clearDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\AIUsageLog')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CreditTransaction')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\UserCredits')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\GenerationSession')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Flashcard')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Deck')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\SystemSettings')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }
    
    private function createDefaultSettings(): void
    {
        $settings = [
            ['default_credits', '500', 'integer'],
            ['ai_generation_cost', '100', 'integer'],
            ['openai_monthly_limit', '1000000', 'integer'],
            ['openai_total_tokens', '0', 'integer'],
        ];
        
        foreach ($settings as [$key, $value, $type]) {
            $setting = new SystemSettings($key, $value, $type);
            $this->entityManager->persist($setting);
        }
        
        $this->entityManager->flush();
    }
    
    private function createUserWithCredits(string $email, int $credits): User
    {
        $user = new User($email);
        $user->setPassword('$2y$13$test');
        $user->setRoles(['ROLE_USER']);
        $user->setIsConfirmed(true);
        
        $this->entityManager->persist($user);
        
        $userCredits = new UserCredits($user, $credits);
        $this->entityManager->persist($userCredits);
        
        $deck = new Deck();
        $deck->setName('Test Deck');
        $deck->setDescription('Test deck');
        $deck->setUser($user);
        $this->entityManager->persist($deck);
        
        $this->entityManager->flush();
        
        return $user;
    }
    
    public function testHandlesRateLimitError(): void
    {
        $user = $this->createUserWithCredits('user@test.com', 500);
        
        // Mock the AI service to throw rate limit error
        $aiService = $this->createMock(\App\Service\AIFlashcardServiceEnhanced::class);
        $aiService->method('checkAvailability')->willReturn(true);
        $aiService->method('estimateCost')->willReturn([
            'tokens' => 1000,
            'estimated_cost' => 0.002,
            'model' => 'gpt-3.5-turbo'
        ]);
        $aiService->method('generateFlashcards')
            ->willThrowException(new OpenAIException(
                'Rate limit exceeded',
                OpenAIException::ERROR_RATE_LIMIT,
                true,
                60
            ));
        
        static::getContainer()->set('App\Service\AIFlashcardServiceEnhanced', $aiService);
        
        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/ai/generate');
        $form = $crawler->selectButton('Generuj fiszki')->form();
        
        $deck = $this->entityManager->getRepository(Deck::class)->findOneBy(['user' => $user]);
        $form['ai_generate[text]'] = str_repeat('Test content ', 100);
        $form['ai_generate[deck]'] = $deck->getId();
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/ai/generate');
        
        $this->client->followRedirect();
        
        // Check for error message
        $this->assertSelectorTextContains('.alert-danger', 'Przekroczono limit zapytań do AI');
        $this->assertSelectorTextContains('.alert-warning', 'Możesz spróbować ponownie za 60 sekund');
        
        // Check that credits were refunded
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        $this->assertEquals(500, $credits->getBalance());
    }
    
    public function testHandlesInsufficientQuotaError(): void
    {
        $user = $this->createUserWithCredits('user@test.com', 500);
        
        // Mock the AI service to throw insufficient quota error
        $aiService = $this->createMock(\App\Service\AIFlashcardServiceEnhanced::class);
        $aiService->method('checkAvailability')->willReturn(true);
        $aiService->method('estimateCost')->willReturn([
            'tokens' => 1000,
            'estimated_cost' => 0.002,
            'model' => 'gpt-3.5-turbo'
        ]);
        $aiService->method('generateFlashcards')
            ->willThrowException(new OpenAIException(
                'You exceeded your current quota',
                OpenAIException::ERROR_INSUFFICIENT_QUOTA,
                false
            ));
        
        static::getContainer()->set('App\Service\AIFlashcardServiceEnhanced', $aiService);
        
        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/ai/generate');
        $form = $crawler->selectButton('Generuj fiszki')->form();
        
        $deck = $this->entityManager->getRepository(Deck::class)->findOneBy(['user' => $user]);
        $form['ai_generate[text]'] = str_repeat('Test content ', 100);
        $form['ai_generate[deck]'] = $deck->getId();
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/ai/generate');
        
        $this->client->followRedirect();
        
        // Check for error message
        $this->assertSelectorTextContains('.alert-danger', 'Brak środków na koncie OpenAI');
        
        // Check that credits were refunded
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        $this->assertEquals(500, $credits->getBalance());
    }
    
    public function testHandlesServiceUnavailable(): void
    {
        $user = $this->createUserWithCredits('user@test.com', 500);
        
        // Mock the AI service to be unavailable
        $aiService = $this->createMock(\App\Service\AIFlashcardServiceEnhanced::class);
        $aiService->method('checkAvailability')->willReturn(false);
        
        static::getContainer()->set('App\Service\AIFlashcardServiceEnhanced', $aiService);
        
        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/ai/generate');
        $form = $crawler->selectButton('Generuj fiszki')->form();
        
        $deck = $this->entityManager->getRepository(Deck::class)->findOneBy(['user' => $user]);
        $form['ai_generate[text]'] = str_repeat('Test content ', 100);
        $form['ai_generate[deck]'] = $deck->getId();
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/ai/generate');
        
        $this->client->followRedirect();
        
        // Check for error message
        $this->assertSelectorTextContains('.alert-danger', 'Serwis AI jest tymczasowo niedostępny');
        
        // Credits should not be charged
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        $this->assertEquals(500, $credits->getBalance());
    }
    
    public function testHandlesMonthlyLimitExceeded(): void
    {
        // Set monthly limit to a very low value
        $setting = $this->entityManager->getRepository(SystemSettings::class)
            ->findByKey('openai_monthly_limit');
        $setting->setValue('100');
        $this->entityManager->flush();
        
        $user = $this->createUserWithCredits('user@test.com', 500);
        
        // Mock AI usage repository to return high usage
        $aiUsageRepo = $this->createMock(\App\Repository\AIUsageLogRepository::class);
        $aiUsageRepo->method('getMonthlyTokenUsage')->willReturn(150);
        
        static::getContainer()->set('App\Repository\AIUsageLogRepository', $aiUsageRepo);
        
        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/ai/generate');
        $form = $crawler->selectButton('Generuj fiszki')->form();
        
        $deck = $this->entityManager->getRepository(Deck::class)->findOneBy(['user' => $user]);
        $form['ai_generate[text]'] = str_repeat('Test content ', 100);
        $form['ai_generate[deck]'] = $deck->getId();
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/ai/generate');
        
        $this->client->followRedirect();
        
        // Check for error message
        $this->assertSelectorTextContains('.alert-danger', 'Przekroczono miesięczny limit tokenów');
    }
    
    public function testHandlesTimeoutError(): void
    {
        $user = $this->createUserWithCredits('user@test.com', 500);
        
        // Mock the AI service to throw timeout error
        $aiService = $this->createMock(\App\Service\AIFlashcardServiceEnhanced::class);
        $aiService->method('checkAvailability')->willReturn(true);
        $aiService->method('estimateCost')->willReturn([
            'tokens' => 1000,
            'estimated_cost' => 0.002,
            'model' => 'gpt-3.5-turbo'
        ]);
        $aiService->method('generateFlashcards')
            ->willThrowException(new OpenAIException(
                'Request timed out',
                OpenAIException::ERROR_TIMEOUT,
                true,
                10
            ));
        
        static::getContainer()->set('App\Service\AIFlashcardServiceEnhanced', $aiService);
        
        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/ai/generate');
        $form = $crawler->selectButton('Generuj fiszki')->form();
        
        $deck = $this->entityManager->getRepository(Deck::class)->findOneBy(['user' => $user]);
        $form['ai_generate[text]'] = str_repeat('Test content ', 100);
        $form['ai_generate[deck]'] = $deck->getId();
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/ai/generate');
        
        $this->client->followRedirect();
        
        // Check for error message
        $this->assertSelectorTextContains('.alert-danger', 'Przekroczono czas oczekiwania');
        $this->assertSelectorTextContains('.alert-warning', 'Możesz spróbować ponownie za 10 sekund');
        
        // Check that credits were refunded
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        $this->assertEquals(500, $credits->getBalance());
    }
}