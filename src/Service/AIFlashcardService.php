<?php

namespace App\Service;

use OpenAI;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AIFlashcardService
{
    private OpenAI\Client $client;
    private LoggerInterface $logger;
    private string $model;
    private float $temperature;
    private int $maxTokens;

    public function __construct(
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        
        $apiKey = $params->get('open_ai_key');
        if (!$apiKey) {
            throw new \RuntimeException('OpenAI API key not configured');
        }
        
        $this->client = OpenAI::client($apiKey);
        $this->model = $params->get('ai_model') ?? 'gpt-3.5-turbo';
        $this->temperature = (float) ($params->get('ai_temperature') ?? 0.7);
        $this->maxTokens = (int) ($params->get('ai_max_tokens') ?? 1000);
    }

    /**
     * Generate flashcards from provided text using OpenAI API
     * 
     * @param string $text Text to generate flashcards from (1000-10000 characters)
     * @return array Array of flashcards with 'front' and 'back' keys
     * @throws \Exception
     */
    public function generateFlashcards(string $text): array
    {
        $textLength = mb_strlen($text);
        
        if ($textLength < 1000 || $textLength > 10000) {
            throw new \InvalidArgumentException(
                sprintf('Text must be between 1000 and 10000 characters, got %d', $textLength)
            );
        }

        try {
            $this->logger->info('Generating flashcards from text', [
                'text_length' => $textLength,
                'model' => $this->model
            ]);

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

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
            ]);

            $content = $response->choices[0]->message->content;
            
            // Parse JSON response
            $flashcards = json_decode($content, true);
            
            if (!is_array($flashcards)) {
                throw new \RuntimeException('Invalid response format from OpenAI');
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
                throw new \RuntimeException('No valid flashcards generated');
            }

            $this->logger->info('Successfully generated flashcards', [
                'count' => count($validatedFlashcards),
                'tokens_used' => $response->usage->totalTokens ?? 0
            ]);

            return $validatedFlashcards;

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate flashcards', [
                'error' => $e->getMessage()
            ]);
            
            throw new \RuntimeException(
                'Failed to generate flashcards: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Estimate the cost of generating flashcards for given text
     * 
     * @param string $text
     * @return array Cost estimation details
     */
    public function estimateCost(string $text): array
    {
        $textTokens = (int) (mb_strlen($text) / 4); // Rough estimation: 1 token ≈ 4 characters
        $responseTokens = $this->maxTokens;
        $totalTokens = $textTokens + $responseTokens;
        
        // Pricing per 1K tokens (adjust based on current OpenAI pricing)
        $pricing = [
            'gpt-3.5-turbo' => 0.002,
            'gpt-4' => 0.03,
            'gpt-4-turbo' => 0.01
        ];
        
        $costPer1K = $pricing[$this->model] ?? $pricing['gpt-3.5-turbo'];
        $estimatedCost = ($totalTokens / 1000) * $costPer1K;
        
        return [
            'estimated_tokens' => $totalTokens,
            'model' => $this->model,
            'estimated_cost_usd' => round($estimatedCost, 4)
        ];
    }
}