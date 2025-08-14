<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserCreditsRepository;
use App\Repository\CreditTransactionRepository;
use App\Repository\SystemSettingsRepository;
use App\Repository\AIUsageLogRepository;
use App\Repository\DoctrineUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserCreditsRepository $creditsRepository,
        private readonly CreditTransactionRepository $transactionRepository,
        private readonly SystemSettingsRepository $settingsRepository,
        private readonly AIUsageLogRepository $aiUsageRepository,
        private readonly DoctrineUserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        $totalUsers = $this->userRepository->count([]);
        $totalCreditsInSystem = $this->creditsRepository->getTotalCreditsInSystem();
        $totalTokensUsed = $this->aiUsageRepository->getTotalTokensUsed();
        $estimatedCost = $this->aiUsageRepository->getEstimatedTotalCost();
        
        $recentTransactions = $this->transactionRepository->getRecentTransactions(10);
        $recentAIUsage = $this->aiUsageRepository->getRecentLogs(10);
        $usersWithLowCredits = $this->creditsRepository->getUsersWithLowCredits(100);
        
        $monthlyTokenUsage = $this->aiUsageRepository->getMonthlyTokenUsage(new \DateTime());
        $totalCreditsGranted = $this->transactionRepository->getTotalCreditsGrantedByAdmin();
        $totalCreditsSpentOnAI = $this->transactionRepository->getTotalCreditsSpentOnAI();
        
        $defaultCredits = $this->settingsRepository->getValue('default_credits', 500);
        $aiGenerationCost = $this->settingsRepository->getValue('ai_generation_cost', 100);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_credits' => $totalCreditsInSystem,
                'total_tokens' => $totalTokensUsed,
                'estimated_cost' => $estimatedCost,
                'monthly_tokens' => $monthlyTokenUsage,
                'credits_granted' => $totalCreditsGranted,
                'credits_spent_ai' => $totalCreditsSpentOnAI,
                'average_credits' => $this->creditsRepository->getAverageCreditsPerUser(),
            ],
            'recent_transactions' => $recentTransactions,
            'recent_ai_usage' => $recentAIUsage,
            'low_credit_users' => $usersWithLowCredits,
            'settings' => [
                'default_credits' => $defaultCredits,
                'ai_generation_cost' => $aiGenerationCost,
            ],
        ]);
    }
}