<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Получить историю диалога для пользователя (последние N сообщений)
     */
    public function findConversationHistory(User $user, int $limit = 10): array
    {
        $rows = $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.role IN (:roles)')
            ->andWhere('m.assistantRole = :arole')
            ->setParameter('user', $user)
            ->setParameter('roles', ['user', 'assistant'])
            ->setParameter('arole', $user->getAiRole())
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();


        return array_reverse($rows); // теперь от меньшего к большему
    }

    /**
     * Получить последние сообщения пользователя для анализа профиля
     */
    public function findRecentUserMessages(int $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.role = :role')
            ->setParameter('user', $userId)
            ->setParameter('role', 'user')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Получить инициативные сообщения за период
     */
    public function findInitiativeMessages(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isInitiative = true')
            ->andWhere('m.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Получить непроанализированные сообщения для обновления профиля
     */
    public function findUnanalyzedUserMessages(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.role = :role')
            ->andWhere('m.usedForAnalysis = false')
            ->setParameter('user', $user)
            ->setParameter('role', 'user')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Получить статистику по токенам для пользователя
     */
    public function getTokenStatistics(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->select([
                'SUM(m.tokens) as total_tokens',
                'AVG(m.tokens) as avg_tokens',
                'COUNT(m.id) as message_count',
                'm.role'
            ])
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->groupBy('m.role')
            ->getQuery()
            ->getResult();
    }

    public function save(Message $message, bool $flush = true): void
    {
        $this->getEntityManager()->persist($message);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
