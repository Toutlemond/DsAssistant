<?php

namespace App\Service;

use App\Entity\MessageSendTask;
use App\Entity\User;
use App\Repository\MessageSendTaskRepository;
use Doctrine\ORM\EntityManagerInterface;

class MessageTaskService
{
    public function __construct(
        private MessageSendTaskRepository $taskRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createTask(User $user, string $text, \DateTimeInterface $sendAt): MessageSendTask
    {
        $task = new MessageSendTask();
        $task->setUser($user);
        $task->setText($text);
        $task->setSendAt(\DateTimeImmutable::createFromInterface($sendAt));

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function scheduleInitiativeMessage(User $user, string $message, int $delayMinutes = 0): MessageSendTask
    {
        $sendAt = new \DateTime("+{$delayMinutes} minutes");
        return $this->createTask($user, $message, $sendAt);
    }

    public function getPendingTasksCount(): int
    {
        return $this->taskRepository->count(['status' => MessageSendTask::STATUS_PENDING]);
    }
}
