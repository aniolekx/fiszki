<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AIUsageLogRepository;
use App\Repository\SystemSettingsRepository;
use App\Service\AIFlashcardServiceEnhanced;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:openai:check-status',
    description: 'Check OpenAI service status and usage statistics',
)]
class CheckOpenAIStatusCommand extends Command
{
    public function __construct(
        private AIFlashcardServiceEnhanced $aiService,
        private AIUsageLogRepository $aiUsageRepository,
        private SystemSettingsRepository $settingsRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('OpenAI Service Status Check');
        
        // Check service availability
        $io->section('Service Availability');
        
        try {
            $available = $this->aiService->checkAvailability();
            
            if ($available) {
                $io->success('✅ OpenAI API is available and responding');
            } else {
                $io->error('❌ OpenAI API is not available');
            }
        } catch (\Exception $e) {
            $io->error('❌ Failed to check availability: ' . $e->getMessage());
        }
        
        // Check monthly usage
        $io->section('Monthly Usage Statistics');
        
        $monthlyLimit = (int) $this->settingsRepository->getValue('openai_monthly_limit', 1000000);
        $currentMonth = new \DateTime();
        $monthlyUsage = $this->aiUsageRepository->getMonthlyTokenUsage($currentMonth);
        $percentage = ($monthlyUsage / $monthlyLimit) * 100;
        
        $io->table(
            ['Metric', 'Value'],
            [
                ['Monthly Limit', number_format($monthlyLimit) . ' tokens'],
                ['Current Usage', number_format($monthlyUsage) . ' tokens'],
                ['Usage Percentage', number_format($percentage, 2) . '%'],
                ['Remaining', number_format($monthlyLimit - $monthlyUsage) . ' tokens'],
            ]
        );
        
        if ($percentage >= 100) {
            $io->error('⚠️ Monthly token limit has been EXCEEDED!');
        } elseif ($percentage >= 90) {
            $io->warning('⚠️ Approaching monthly limit (>90% used)');
        } elseif ($percentage >= 75) {
            $io->warning('⚠️ High usage detected (>75% used)');
        } else {
            $io->success('✅ Usage is within normal limits');
        }
        
        // Total usage statistics
        $io->section('Total Usage Statistics');
        
        $totalTokens = $this->aiUsageRepository->getTotalTokensUsed();
        $estimatedCost = $this->aiUsageRepository->getEstimatedTotalCost();
        $totalLogs = $this->aiUsageRepository->count([]);
        
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total API Calls', number_format($totalLogs)],
                ['Total Tokens Used', number_format($totalTokens)],
                ['Estimated Total Cost', '$' . number_format($estimatedCost, 4)],
                ['Average Tokens per Call', $totalLogs > 0 ? number_format($totalTokens / $totalLogs) : '0'],
            ]
        );
        
        // Recent errors
        $io->section('Recent Activity');
        
        $recentLogs = $this->aiUsageRepository->getRecentLogs(5);
        
        if (empty($recentLogs)) {
            $io->info('No recent API activity');
        } else {
            $rows = [];
            foreach ($recentLogs as $log) {
                $rows[] = [
                    $log->getCreatedAt()->format('Y-m-d H:i'),
                    $log->getUser()->getEmail(),
                    number_format($log->getTokensUsed()),
                    '$' . number_format($log->getEstimatedCost() ?? 0, 4),
                    $log->getModel()
                ];
            }
            
            $io->table(
                ['Date', 'User', 'Tokens', 'Cost', 'Model'],
                $rows
            );
        }
        
        // Recommendations
        $io->section('Recommendations');
        
        if ($percentage >= 90) {
            $io->warning([
                '• Consider increasing the monthly token limit',
                '• Review usage patterns to optimize token consumption',
                '• Consider implementing caching for frequent requests'
            ]);
        }
        
        if ($estimatedCost > 100) {
            $io->warning([
                '• High costs detected - review model selection',
                '• Consider using gpt-3.5-turbo instead of gpt-4 for lower costs',
                '• Implement request batching where possible'
            ]);
        }
        
        $io->success('Status check completed');
        
        return Command::SUCCESS;
    }
}