<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AIUsageLog;
use App\Entity\SystemSettings;
use App\Exception\OpenAIException;
use App\Repository\AIUsageLogRepository;
use App\Repository\SystemSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AIFlashcardServiceEnhanced
{
    private OpenAI\Client $client;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private SystemSettingsRepository $settingsRepository;
    private AIUsageLogRepository $aiUsageRepository;
    private string $model;
    private float $temperature;
    private int $maxTokens;
    private int $maxRetries = 3;
    private int $retryDelay = 1000; // milliseconds
    
    public function __construct(
        ParameterBagInterface $params,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        SystemSettingsRepository $settingsRepository,
        AIUsageLogRepository $aiUsageRepository
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->settingsRepository = $settingsRepository;
        $this->aiUsageRepository = $aiUsageRepository;
        
        $apiKey = $params->get('open_ai_key');
        if (!$apiKey) {
            throw new OpenAIException(
                'OpenAI API key not configured',
                OpenAIException::ERROR_INVALID_API_KEY
            );
        }
        
        try {
            $this->client = OpenAI::client($apiKey);
        } catch (\Exception $e) {
            throw OpenAIException::fromOpenAIError($e);
        }
        
        $this->model = $params->get('ai_model') ?? 'gpt-3.5-turbo';
        $this->temperature = (float) ($params->get('ai_temperature') ?? 0.7);
        $this->maxTokens = (int) ($params->get('ai_max_tokens') ?? 1000);
    }
    
    /**
     * Check if we're within monthly token limit
     */
    private function checkMonthlyLimit(): void
    {
        $monthlyLimit = (int) $this->settingsRepository->getValue(
            SystemSettings::OPENAI_MONTHLY_LIMIT,
            1000000
        );
        
        $currentMonthUsage = $this->aiUsageRepository->getMonthlyTokenUsage(new \DateTime());
        
        if ($currentMonthUsage >= $monthlyLimit) {
            $this->logger->critical('Monthly OpenAI token limit exceeded', [
                'limit' => $monthlyLimit,
                'usage' => $currentMonthUsage
            ]);
            
            throw new OpenAIException(
                sprintf('Przekroczono miesięczny limit tokenów (%d/%d)', $currentMonthUsage, $monthlyLimit),
                OpenAIException::ERROR_RATE_LIMIT,
                false
            );
        }
        
        // Warn if approaching limit (90%)
        if ($currentMonthUsage > $monthlyLimit * 0.9) {
            $this->logger->warning('Approaching monthly OpenAI token limit', [
                'limit' => $monthlyLimit,
                'usage' => $currentMonthUsage,
                'percentage' => round(($currentMonthUsage / $monthlyLimit) * 100, 2)
            ]);
        }
    }
    
    /**
     * Generate flashcards with retry mechanism and enhanced error handling
     */
    public function generateFlashcards(string $text, ?int $userId = null): array
    {
        $textLength = mb_strlen($text);
        
        if ($textLength < 1000 || $textLength > 10000) {
            throw new \InvalidArgumentException(
                sprintf('Text must be between 1000 and 10000 characters, got %d', $textLength)
            );
        }
        
        // Check monthly limit before making request
        $this->checkMonthlyLimit();
        
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            try {
                $this->logger->info('Attempting to generate flashcards', [
                    'attempt' => $attempt,
                    'text_length' => $textLength,
                    'model' => $this->model
                ]);
                
                $result = $this->makeOpenAIRequest($text);
                
                // Log successful generation
                $this->logUsage($result['tokens_used'], $result['estimated_cost'], $userId);
                
                return $result['flashcards'];
                
            } catch (OpenAIException $e) {
                $lastException = $e;
                
                $this->logger->error('OpenAI API error', [
                    'attempt' => $attempt,
                    'error_type' => $e->getErrorType(),
                    'message' => $e->getMessage(),
                    'retryable' => $e->isRetryable()
                ]);
                
                // If not retryable or last attempt, throw immediately
                if (!$e->isRetryable() || $attempt >= $this->maxRetries) {
                    throw $e;
                }
                
                // Calculate delay
                $delay = $e->getRetryAfter() 
                    ? $e->getRetryAfter() * 1000 
                    : $this->retryDelay * pow(2, $attempt - 1); // Exponential backoff
                
                $this->logger->info('Retrying after delay', [
                    'delay_ms' => $delay,
                    'next_attempt' => $attempt + 1
                ]);
                
                usleep($delay * 1000); // Convert to microseconds
                
            } catch (\Exception $e) {
                // Convert unknown exceptions to OpenAIException
                $openAIException = OpenAIException::fromOpenAIError($e);
                $lastException = $openAIException;
                
                $this->logger->error('Unexpected error during flashcard generation', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                if (!$openAIException->isRetryable() || $attempt >= $this->maxRetries) {
                    throw $openAIException;
                }
                
                usleep($this->retryDelay * 1000 * pow(2, $attempt - 1));
            }
        }
        
        // If we get here, all retries failed
        throw $lastException ?? new OpenAIException(
            'Failed to generate flashcards after ' . $this->maxRetries . ' attempts',
            OpenAIException::ERROR_UNKNOWN
        );
    }
    
    /**
     * Make the actual OpenAI API request
     */
    private function makeOpenAIRequest(string $text): array
    {
        $systemPrompt = "Jesteś ekspertem w tworzeniu fiszek edukacyjnych. Tworzysz przejrzyste, konkretne pytania i odpowiedzi, które pomogą w nauce i zapamiętywaniu materiału.";
        
        $userPrompt = sprintf(
            "Wygeneruj fiszki edukacyjne na podstawie poniższego tekstu.\n" .
            "Każda fiszka powinna mieć pytanie (front) i odpowiedź (back).\n" .
            "Wygeneruj 5-10 najważniejszych fiszek.\n" .
            "Odpowiedz TYLKO w formacie JSON, bez dodatkowego tekstu:\n" .
            "[{\"front\": \"pytanie\", \"back\": \"odpowiedź\"}]\n\n" .
            "Tekst: %s",
            $text
        );
        
        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
                'timeout' => 30, // 30 seconds timeout
            ]);
        } catch (\Exception $e) {
            // Parse specific OpenAI errors
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response && method_exists($response, 'getBody')) {
                    $body = json_decode($response->getBody()->getContents(), true);
                    if (isset($body['error']['type'])) {
                        throw OpenAIException::fromOpenAIError($e);
                    }
                }
            }
            throw OpenAIException::fromOpenAIError($e);
        }
        
        $content = $response->choices[0]->message->content;
        $tokensUsed = $response->usage->totalTokens ?? 0;
        
        // Parse JSON response
        $flashcards = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new OpenAIException(
                'Invalid JSON response from OpenAI: ' . json_last_error_msg(),
                OpenAIException::ERROR_UNKNOWN
            );
        }
        
        if (!is_array($flashcards)) {
            throw new OpenAIException(
                'Invalid response format from OpenAI - expected array',
                OpenAIException::ERROR_UNKNOWN
            );
        }
        
        // Validate flashcard structure
        $validatedFlashcards = [];
        foreach ($flashcards as $flashcard) {
            if (isset($flashcard['front']) && isset($flashcard['back'])) {
                $validatedFlashcards[] = [
                    'front' => trim($flashcard['front']),
                    'back' => trim($flashcard['back'])
                ];
            }
        }
        
        if (empty($validatedFlashcards)) {
            throw new OpenAIException(
                'No valid flashcards generated from response',
                OpenAIException::ERROR_UNKNOWN
            );
        }
        
        $this->logger->info('Successfully generated flashcards', [
            'count' => count($validatedFlashcards),
            'tokens_used' => $tokensUsed
        ]);
        
        return [
            'flashcards' => $validatedFlashcards,
            'tokens_used' => $tokensUsed,
            'estimated_cost' => $this->calculateCost($tokensUsed)
        ];
    }
    
    /**
     * Log API usage
     */
    private function logUsage(int $tokensUsed, float $estimatedCost, ?int $userId = null): void
    {
        try {
            // Update total token counter in settings
            $totalTokens = (int) $this->settingsRepository->getValue(
                SystemSettings::OPENAI_TOTAL_TOKENS,
                0
            );
            
            $this->settingsRepository->setValue(
                SystemSettings::OPENAI_TOTAL_TOKENS,
                $totalTokens + $tokensUsed,
                'integer'
            );
            
            $this->logger->info('OpenAI usage logged', [
                'tokens' => $tokensUsed,
                'cost' => $estimatedCost,
                'total_tokens' => $totalTokens + $tokensUsed
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to log OpenAI usage', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Calculate estimated cost based on tokens
     */
    private function calculateCost(int $tokens): float
    {
        $pricing = [
            'gpt-3.5-turbo' => 0.002,
            'gpt-4' => 0.03,
            'gpt-4-turbo' => 0.01,
            'gpt-4o' => 0.005,
            'gpt-4o-mini' => 0.00015
        ];
        
        $costPer1K = $pricing[$this->model] ?? $pricing['gpt-3.5-turbo'];
        
        return round(($tokens / 1000) * $costPer1K, 4);
    }
    
    /**
     * Estimate the cost before making the request
     */
    public function estimateCost(string $text): array
    {
        $textTokens = (int) (mb_strlen($text) / 4); // Rough estimation
        $responseTokens = min($this->maxTokens, 500); // Typical response size
        $totalTokens = $textTokens + $responseTokens;
        
        return [
            'tokens' => $totalTokens,
            'estimated_cost' => $this->calculateCost($totalTokens),
            'model' => $this->model
        ];
    }
    
    /**
     * Check if the service is available
     */
    public function checkAvailability(): bool
    {
        try {
            // Check monthly limit
            $this->checkMonthlyLimit();
            
            // Try a simple API call to verify connection
            $response = $this->client->models()->list();
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('OpenAI service availability check failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}