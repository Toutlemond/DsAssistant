<?php

namespace App\Repository;

use App\Entity\Focus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Focus>
 */
class FocusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Focus::class);
    }

    /**
     * Возвращает следующий фокус (самый приоритетный, new)
     */
    public function findNextNew(): ?Focus
    {
        return $this->createQueryBuilder('f')
            ->where('f.status = :status')
            ->setParameter('status', Focus::STATUS_NEW)
            ->orderBy('f.priority', 'DESC')
            ->addOrderBy('f.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Возвращает следующий фокус для обработки (самый приоритетный, pending)
     */
    public function findNextPending(): ?Focus
    {
        return $this->createQueryBuilder('f')
            ->where('f.status = :status')
            ->setParameter('status', Focus::STATUS_PENDING)
            ->orderBy('f.priority', 'DESC')
            ->addOrderBy('f.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Focus $focus, bool $flush = true): void
    {
        $this->getEntityManager()->persist($focus);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $coll
     * @return mixed
     */
    public function findAllOrderBy(string $coll = 'id')
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.'.$coll, 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}
