<?php

namespace App\Repository;

use App\Entity\Thought;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Thought>
 */
class ThoughtRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Thought::class);
    }

    public function save(Thought $thought, bool $flush = true): void
    {
        $this->getEntityManager()->persist($thought);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Возвращает последние мысли пользователя (для контекста)
     */
    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
