<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(\App\Entity\User $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findByEmail(string $email): ?\App\Entity\User
    {
        return $this->findOneBy(['email' => $email]);
    }
} 