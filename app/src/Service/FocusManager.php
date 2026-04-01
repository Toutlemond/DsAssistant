<?php

namespace App\Service;

use App\Entity\Focus;
use App\Entity\User;
use App\Repository\FocusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FocusManager
{
    public function __construct(
        private FocusRepository $focusRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Создаёт новый фокус
     */
    public function createFocus(
        string $topic,
        string $source,
        ?User $user = null,
        int $priority = 5,
        ?array $context = null
    ): Focus {
        $focus = new Focus();
        $focus->setTopic($topic);
        $focus->setSource($source);
        $focus->setUser($user);
        $focus->setPriority($priority);
        $focus->setContext($context);
        $focus->setStatus(Focus::STATUS_NEW);

        $this->focusRepository->save($focus);

        $this->logger->info('Focus created', [
            'topic' => $topic,
            'source' => $source,
            'user_id' => $user?->getId()
        ]);

        return $focus;
    }

    /**
     * Получить следующий фокус для обработки
     */
    public function getNextFocus(): ?Focus
    {
        return $this->focusRepository->findNextPending();
    }

    /**
     * Отметить фокус как обработанный
     */
    public function markAsDone(Focus $focus, ?string $result = null): void
    {
        $focus->setStatus(Focus::STATUS_DONE);
        $focus->setProcessedAt(new \DateTimeImmutable());
        // Можно сохранить результат в контекст, если нужно
        if ($result !== null) {
            $context = $focus->getContext() ?? [];
            $context['result'] = $result;
            $focus->setContext($context);
        }
        $this->entityManager->flush();

        $this->logger->info('Focus processed', [
            'id' => $focus->getId(),
            'topic' => $focus->getTopic()
        ]);
    }

    /**
     * Отметить фокус как ошибочный (для повторной обработки позже)
     */
    public function markAsPending(Focus $focus): void
    {
        $focus->setStatus(Focus::STATUS_PENDING);
        $focus->setProcessedAt(null);
        $this->entityManager->flush();
    }
}
