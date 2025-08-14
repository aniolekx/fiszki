<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CreditTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditTransaction>
 */
class CreditTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditTransaction::class);
    }

    public function findByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ct.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCreditsGrantedByAdmin(): int
    {
        $result = $this->createQueryBuilder('ct')
            ->select('SUM(ct.amount) as total')
            ->where('ct.type = :type')
            ->andWhere('ct.amount > 0')
            ->setParameter('type', CreditTransaction::TYPE_ADMIN_GRANT)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getTotalCreditsSpentOnAI(): int
    {
        $result = $this->createQueryBuilder('ct')
            ->select('SUM(ABS(ct.amount)) as total')
            ->where('ct.type = :type')
            ->setParameter('type', CreditTransaction::TYPE_AI_GENERATION)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getRecentTransactions(int $limit = 20): array
    {
        return $this->createQueryBuilder('ct')
            ->leftJoin('ct.user', 'u')
            ->orderBy('ct.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}