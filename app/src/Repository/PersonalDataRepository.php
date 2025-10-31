<?php

namespace App\Repository;

use App\Entity\PersonalData;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonalData>
 */
class PersonalDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalData::class);
    }

    /**
     * Найти все персональные данные пользователя
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.type', 'ASC')
            ->addOrderBy('p.key', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти персональные данные пользователя по типу
     */
    public function findByUserAndType(User $user, string $type): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('p.key', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти конкретную запись по пользователю, типу и ключу
     */
    public function findOneByUserTypeAndKey(User $user, string $type, string $key): ?PersonalData
    {
        return $this->findOneBy([
            'user' => $user,
            'type' => $type,
            'key' => $key
        ]);
    }

    /**
     * Получить все напоминания пользователя
     */
    public function findRemindersByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'reminder')
            ->orderBy('p.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти будущие события пользователя
     */
    public function findFutureEventsByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'future_event')
            ->orderBy('p.interestLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти людей пользователя
     */
    public function findPeopleByUser(User $user): array
    {
        return $this->findByUserAndType($user, 'person');
    }

    /**
     * Найти домашних животных пользователя
     */
    public function findPetsByUser(User $user): array
    {
        return $this->findByUserAndType($user, 'pet');
    }

    /**
     * Найти места пользователя
     */
    public function findLocationsByUser(User $user): array
    {
        return $this->findByUserAndType($user, 'location');
    }

    /**
     * Найти предпочтения пользователя
     */
    public function findPreferencesByUser(User $user): array
    {
        return $this->findByUserAndType($user, 'preference');
    }

    /**
     * Найти важные даты пользователя
     */
    public function findImportantDatesByUser(User $user): array
    {
        return $this->findByUserAndType($user, 'important_date');
    }

    /**
     * Удалить все персональные данные пользователя определенного типа
     */
    public function deleteByUserAndType(User $user, string $type): int
    {
        return $this->createQueryBuilder('p')
            ->delete()
            ->where('p.user = :user')
            ->andWhere('p.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->execute();
    }

    public function save(PersonalData $personalData, bool $flush = true): void
    {
        $this->getEntityManager()->persist($personalData);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PersonalData $personalData, bool $flush = true): void
    {
        $this->getEntityManager()->remove($personalData);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
