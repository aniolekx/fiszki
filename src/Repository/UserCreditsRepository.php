<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserCredits;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCredits>
 */
class UserCreditsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCredits::class);
    }

    public function findByUser(User $user): ?UserCredits
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function getTotalCreditsInSystem(): int
    {
        $result = $this->createQueryBuilder('uc')
            ->select('SUM(uc.balance) as total')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getUsersWithLowCredits(int $threshold = 100): array
    {
        return $this->createQueryBuilder('uc')
            ->where('uc.balance < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function getAverageCreditsPerUser(): float
    {
        $result = $this->createQueryBuilder('uc')
            ->select('AVG(uc.balance) as average')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}