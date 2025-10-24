<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;

class MessageService
{
    public function __construct(
        private MessageRepository $messageRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createMessage(
        User $user,
        string $content,
        string $role,
        ?string $messageType = 'text',
        ?int $telegramMessageId = null,
        ?int $tokens = null,
        ?array $metadata = null,
        ?bool $isInitiative = false,
        ?string $initiativeTrigger = null
    ): Message {
        $message = new Message();
        $message->setUser($user);
        $message->setContent($content);
        $message->setRole($role);
        $message->setMessageType($messageType);
        $message->setTelegramMessageId($telegramMessageId);
        $message->setTokens($tokens);
        $message->setMetadata($metadata);
        $message->setIsInitiative($isInitiative);
        $message->setInitiativeTrigger($initiativeTrigger);

        $this->messageRepository->save($message);

        return $message;
    }

    /**
     * Получить историю диалога в формате для DeepSeek API
     */
    public function getDeepSeekFormatHistory(User $user, int $limit = 10): array
    {
        $messages = $this->messageRepository->findConversationHistory($user, $limit);

        return array_map(function (Message $message) {
            return [
                'role' => $message->getRole(),
                'content' => $message->getContent(),
                'timestamp' => $message->getCreatedAt()->format('c'),
                'tokens' => $message->getTokens()
            ];
        }, $messages);
    }

    /**
     * Пометить сообщения как проанализированные
     */
    public function markMessagesAsAnalyzed(array $messages): void
    {
        foreach ($messages as $message) {
            $message->setUsedForAnalysis(true);
        }

        $this->entityManager->flush();
    }

    /**
     * Очистить старую историю (архивация/удаление)
     */
    public function cleanupOldMessages(int $daysOld = 30): int
    {
        $date = new \DateTime("-$daysOld days");

        $query = $this->entityManager->createQuery(
            'DELETE FROM App\Entity\Message m WHERE m.createdAt < :date'
        )->setParameter('date', $date);

        return $query->execute();
    }
}
