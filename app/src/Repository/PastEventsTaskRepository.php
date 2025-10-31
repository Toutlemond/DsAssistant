<?php

namespace App\Repository;

use App\Entity\PastEventsTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PastEventsTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PastEventsTask::class);
    }

    public function findReadyForProcessing(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isProcessed = false')
            ->andWhere('t.suggestedRemindAt <= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function findUserFutureEvents(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :userId')
            ->andWhere('t.isProcessed = false')
            ->setParameter('userId', $userId)
            ->orderBy('t.suggestedRemindAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
