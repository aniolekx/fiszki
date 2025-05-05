<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FeatureContext extends RawMinkContext implements Context
{
    private KernelInterface $kernel;
    private EntityManagerInterface $entityManager;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $entityManager)
    {
        $this->kernel = $kernel;
        $this->entityManager = $entityManager;
    }

    /**
     * @BeforeScenario
     */
    public function clearDatabase(): void
    {
                // === POCZĄTEK TYMCZASOWEGO DEBUGOWANIA ===
                echo "--- DEBUG: Inside clearDatabase() ---\n";
                echo "Getenv DATABASE_URL: " . (getenv('DATABASE_URL') ?: 'NOT FOUND') . "\n";
                echo "SERVER DATABASE_URL: " . ($_SERVER['DATABASE_URL'] ?? 'NOT FOUND') . "\n";
                echo "ENV DATABASE_URL: " . ($_ENV['DATABASE_URL'] ?? 'NOT FOUND') . "\n";
                // Możesz tymczasowo zatrzymać wykonywanie, żeby zobaczyć tylko ten output:
                // die("--- DEBUG: Stopping after checking env vars ---");
                // === KONIEC TYMCZASOWEGO DEBUGOWANIA ===
                
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE doctrine_migration_versions');
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
    }
} 