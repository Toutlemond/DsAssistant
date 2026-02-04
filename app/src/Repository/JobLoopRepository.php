<?php

namespace App\Repository;

use App\Entity\JobLoop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobLoopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobLoop::class);
    }

    public function findActiveLoops(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.isActive = true')
            ->getQuery()
            ->getResult();
    }
    public function findAll(): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.command', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findLoopByCommand(string $command): ?JobLoop
    {
        return $this->findOneBy(['command' => $command, 'isActive' => true]);
    }
}
