<?php

declare(strict_types=1);

namespace App\Exception;

class OpenAIException extends \RuntimeException
{
    public const ERROR_RATE_LIMIT = 'rate_limit';
    public const ERROR_INSUFFICIENT_QUOTA = 'insufficient_quota';
    public const ERROR_SERVICE_UNAVAILABLE = 'service_unavailable';
    public const ERROR_INVALID_API_KEY = 'invalid_api_key';
    public const ERROR_TIMEOUT = 'timeout';
    public const ERROR_NETWORK = 'network';
    public const ERROR_UNKNOWN = 'unknown';
    
    private string $errorType;
    private bool $isRetryable;
    private ?int $retryAfter;
    
    public function __construct(
        string $message,
        string $errorType = self::ERROR_UNKNOWN,
        bool $isRetryable = false,
        ?int $retryAfter = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->isRetryable = $isRetryable;
        $this->retryAfter = $retryAfter;
    }
    
    public function getErrorType(): string
    {
        return $this->errorType;
    }
    
    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }
    
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
    
    public function getUserMessage(): string
    {
        return match($this->errorType) {
            self::ERROR_RATE_LIMIT => 'Przekroczono limit zapytań do AI. Spróbuj ponownie za chwilę.',
            self::ERROR_INSUFFICIENT_QUOTA => 'Brak środków na koncie OpenAI. Skontaktuj się z administratorem.',
            self::ERROR_SERVICE_UNAVAILABLE => 'Serwis AI jest tymczasowo niedostępny. Spróbuj ponownie później.',
            self::ERROR_INVALID_API_KEY => 'Nieprawidłowy klucz API. Skontaktuj się z administratorem.',
            self::ERROR_TIMEOUT => 'Przekroczono czas oczekiwania na odpowiedź. Spróbuj ponownie.',
            self::ERROR_NETWORK => 'Błąd połączenia z serwerem AI. Sprawdź połączenie internetowe.',
            default => 'Wystąpił błąd podczas generowania fiszek. Spróbuj ponownie.',
        };
    }
    
    public static function fromOpenAIError(\Throwable $error): self
    {
        $message = $error->getMessage();
        $code = $error->getCode();
        
        // Parse OpenAI error messages
        if (str_contains($message, 'Rate limit') || str_contains($message, 'rate_limit')) {
            return new self(
                $message,
                self::ERROR_RATE_LIMIT,
                true,
                60, // Retry after 60 seconds
                429,
                $error
            );
        }
        
        if (str_contains($message, 'insufficient_quota') || str_contains($message, 'You exceeded your current quota')) {
            return new self(
                $message,
                self::ERROR_INSUFFICIENT_QUOTA,
                false,
                null,
                402,
                $error
            );
        }
        
        if (str_contains($message, 'Service Unavailable') || $code === 503) {
            return new self(
                $message,
                self::ERROR_SERVICE_UNAVAILABLE,
                true,
                30,
                503,
                $error
            );
        }
        
        if (str_contains($message, 'Incorrect API key') || str_contains($message, 'invalid_api_key')) {
            return new self(
                $message,
                self::ERROR_INVALID_API_KEY,
                false,
                null,
                401,
                $error
            );
        }
        
        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return new self(
                $message,
                self::ERROR_TIMEOUT,
                true,
                10,
                408,
                $error
            );
        }
        
        if (str_contains($message, 'cURL error') || str_contains($message, 'network')) {
            return new self(
                $message,
                self::ERROR_NETWORK,
                true,
                5,
                0,
                $error
            );
        }
        
        return new self(
            $message,
            self::ERROR_UNKNOWN,
            false,
            null,
            $code,
            $error
        );
    }
}