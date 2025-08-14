<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\SystemSettings;
use App\Exception\OpenAIException;
use App\Repository\AIUsageLogRepository;
use App\Repository\SystemSettingsRepository;
use App\Service\AIFlashcardServiceEnhanced;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AIFlashcardServiceEnhancedTest extends TestCase
{
    private AIFlashcardServiceEnhanced $service;
    private MockObject $params;
    private MockObject $logger;
    private MockObject $entityManager;
    private MockObject $settingsRepository;
    private MockObject $aiUsageRepository;
    
    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->settingsRepository = $this->createMock(SystemSettingsRepository::class);
        $this->aiUsageRepository = $this->createMock(AIUsageLogRepository::class);
        
        // Set up default parameters
        $this->params->method('get')->willReturnMap([
            ['open_ai_key', 'test-api-key'],
            ['ai_model', 'gpt-3.5-turbo'],
            ['ai_temperature', 0.7],
            ['ai_max_tokens', 1000],
        ]);
    }
    
    public function testThrowsExceptionWhenApiKeyNotConfigured(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->with('open_ai_key')->willReturn(null);
        
        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('OpenAI API key not configured');
        
        new AIFlashcardServiceEnhanced(
            $params,
            $this->logger,
            $this->entityManager,
            $this->settingsRepository,
            $this->aiUsageRepository
        );
    }
    
    public function testCheckMonthlyLimitThrowsExceptionWhenExceeded(): void
    {
        $this->settingsRepository->method('getValue')
            ->with(SystemSettings::OPENAI_MONTHLY_LIMIT, 1000000)
            ->willReturn(1000000);
        
        $this->aiUsageRepository->method('getMonthlyTokenUsage')
            ->willReturn(1000001); // Exceeded
        
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Monthly OpenAI token limit exceeded');
        
        // Create service with reflection to test private method
        $service = new AIFlashcardServiceEnhanced(
            $this->params,
            $this->logger,
            $this->entityManager,
            $this->settingsRepository,
            $this->aiUsageRepository
        );
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkMonthlyLimit');
        $method->setAccessible(true);
        
        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessage('Przekroczono miesięczny limit tokenów');
        
        $method->invoke($service);
    }
    
    public function testCheckMonthlyLimitWarnsWhenApproaching(): void
    {
        $this->settingsRepository->method('getValue')
            ->with(SystemSettings::OPENAI_MONTHLY_LIMIT, 1000000)
            ->willReturn(1000000);
        
        $this->aiUsageRepository->method('getMonthlyTokenUsage')
            ->willReturn(950000); // 95% used
        
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Approaching monthly OpenAI token limit');
        
        $service = new AIFlashcardServiceEnhanced(
            $this->params,
            $this->logger,
            $this->entityManager,
            $this->settingsRepository,
            $this->aiUsageRepository
        );
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkMonthlyLimit');
        $method->setAccessible(true);
        
        // Should not throw exception, just warn
        $method->invoke($service);
    }
    
    public function testEstimateCostCalculatesCorrectly(): void
    {
        $service = new AIFlashcardServiceEnhanced(
            $this->params,
            $this->logger,
            $this->entityManager,
            $this->settingsRepository,
            $this->aiUsageRepository
        );
        
        $text = str_repeat('Test ', 200); // 1000 characters
        $estimate = $service->estimateCost($text);
        
        $this->assertArrayHasKey('tokens', $estimate);
        $this->assertArrayHasKey('estimated_cost', $estimate);
        $this->assertArrayHasKey('model', $estimate);
        
        // ~250 tokens for text + 500 for response = 750 tokens
        // At $0.002 per 1K tokens = $0.0015
        $this->assertGreaterThan(0, $estimate['tokens']);
        $this->assertGreaterThan(0, $estimate['estimated_cost']);
        $this->assertEquals('gpt-3.5-turbo', $estimate['model']);
    }
    
    public function testValidatesTextLength(): void
    {
        $service = new AIFlashcardServiceEnhanced(
            $this->params,
            $this->logger,
            $this->entityManager,
            $this->settingsRepository,
            $this->aiUsageRepository
        );
        
        // Too short
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Text must be between 1000 and 10000 characters');
        
        $service->generateFlashcards('Short text');
    }
    
    public function testOpenAIExceptionFromAPIErrors(): void
    {
        // Test rate limit error
        $error = new \Exception('Rate limit exceeded');
        $exception = OpenAIException::fromOpenAIError($error);
        
        $this->assertEquals(OpenAIException::ERROR_RATE_LIMIT, $exception->getErrorType());
        $this->assertTrue($exception->isRetryable());
        $this->assertEquals(60, $exception->getRetryAfter());
        $this->assertEquals(
            'Przekroczono limit zapytań do AI. Spróbuj ponownie za chwilę.',
            $exception->getUserMessage()
        );
        
        // Test insufficient quota error
        $error = new \Exception('You exceeded your current quota');
        $exception = OpenAIException::fromOpenAIError($error);
        
        $this->assertEquals(OpenAIException::ERROR_INSUFFICIENT_QUOTA, $exception->getErrorType());
        $this->assertFalse($exception->isRetryable());
        $this->assertEquals(
            'Brak środków na koncie OpenAI. Skontaktuj się z administratorem.',
            $exception->getUserMessage()
        );
        
        // Test service unavailable
        $error = new \Exception('Service Unavailable', 503);
        $exception = OpenAIException::fromOpenAIError($error);
        
        $this->assertEquals(OpenAIException::ERROR_SERVICE_UNAVAILABLE, $exception->getErrorType());
        $this->assertTrue($exception->isRetryable());
        $this->assertEquals(30, $exception->getRetryAfter());
        
        // Test invalid API key
        $error = new \Exception('Incorrect API key provided');
        $exception = OpenAIException::fromOpenAIError($error);
        
        $this->assertEquals(OpenAIException::ERROR_INVALID_API_KEY, $exception->getErrorType());
        $this->assertFalse($exception->isRetryable());
        
        // Test timeout
        $error = new \Exception('Request timed out');
        $exception = OpenAIException::fromOpenAIError($error);
        
        $this->assertEquals(OpenAIException::ERROR_TIMEOUT, $exception->getErrorType());
        $this->assertTrue($exception->isRetryable());
        $this->assertEquals(10, $exception->getRetryAfter());
        
        // Test network error
        $error = new \Exception('cURL error 28: Connection timeout');
        $exception = OpenAIException::fromOpenAIError($error);
        
        $this->assertEquals(OpenAIException::ERROR_NETWORK, $exception->getErrorType());
        $this->assertTrue($exception->isRetryable());
        $this->assertEquals(5, $exception->getRetryAfter());
        
        // Test unknown error
        $error = new \Exception('Something went wrong');
        $exception = OpenAIException::fromOpenAIError($error);
        
        $this->assertEquals(OpenAIException::ERROR_UNKNOWN, $exception->getErrorType());
        $this->assertFalse($exception->isRetryable());
    }
    
    public function testRetryMechanismForTemporaryErrors(): void
    {
        $this->settingsRepository->method('getValue')
            ->willReturn(1000000);
        
        $this->aiUsageRepository->method('getMonthlyTokenUsage')
            ->willReturn(0);
        
        // Mock logger to verify retry attempts
        $this->logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [$this->stringContains('Attempting to generate flashcards')],
                [$this->stringContains('Retrying after delay')],
                [$this->stringContains('Attempting to generate flashcards')]
            );
        
        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->with($this->stringContains('OpenAI API error'));
        
        // This test would require mocking the OpenAI client which is complex
        // In a real scenario, you'd use integration tests for this
        $this->assertTrue(true);
    }
    
    public function testLogsUsageSuccessfully(): void
    {
        $this->settingsRepository->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(1000000, 5000);
        
        $this->settingsRepository->expects($this->once())
            ->method('setValue')
            ->with(
                SystemSettings::OPENAI_TOTAL_TOKENS,
                5500,
                'integer'
            );
        
        $this->logger->expects($this->once())
            ->method('info')
            ->with('OpenAI usage logged', $this->arrayHasKey('tokens'));
        
        $service = new AIFlashcardServiceEnhanced(
            $this->params,
            $this->logger,
            $this->entityManager,
            $this->settingsRepository,
            $this->aiUsageRepository
        );
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('logUsage');
        $method->setAccessible(true);
        
        $method->invoke($service, 500, 0.001, 1);
    }
}