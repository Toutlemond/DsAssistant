<?php

namespace App\Repository;

use App\Entity\MessageSendTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageSendTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageSendTask::class);
    }

    public function findPendingTasks(\DateTimeInterface $maxSendAt = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.sendAt <= :now')
            ->setParameter('status', MessageSendTask::STATUS_PENDING)
            ->setParameter('now', $maxSendAt ?: new \DateTime())
            ->orderBy('t.sendAt', 'ASC')
            ->setMaxResults(100);

        return $qb->getQuery()->getResult();
    }

    public function findUserPendingTasks(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.user = :userId')
            ->setParameter('status', MessageSendTask::STATUS_PENDING)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
