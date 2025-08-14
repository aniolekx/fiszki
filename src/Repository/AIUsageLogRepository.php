<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AIUsageLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AIUsageLog>
 */
class AIUsageLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AIUsageLog::class);
    }

    public function getTotalTokensUsed(): int
    {
        $result = $this->createQueryBuilder('log')
            ->select('SUM(log.tokensUsed) as total')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getTotalTokensByUser(User $user): int
    {
        $result = $this->createQueryBuilder('log')
            ->select('SUM(log.tokensUsed) as total')
            ->where('log.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getMonthlyTokenUsage(\DateTimeInterface $month): int
    {
        $startDate = clone $month;
        $startDate->modify('first day of this month')->setTime(0, 0, 0);
        
        $endDate = clone $month;
        $endDate->modify('last day of this month')->setTime(23, 59, 59);

        $result = $this->createQueryBuilder('log')
            ->select('SUM(log.tokensUsed) as total')
            ->where('log.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getRecentLogs(int $limit = 20): array
    {
        return $this->createQueryBuilder('log')
            ->leftJoin('log.user', 'u')
            ->orderBy('log.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getEstimatedTotalCost(): float
    {
        $result = $this->createQueryBuilder('log')
            ->select('SUM(log.estimatedCost) as total')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}