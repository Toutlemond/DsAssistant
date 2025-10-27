<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UserDiscussionService
{
    private TelegramBotService $telegramBotService;
    private UserRepository $userRepository;
    private DeepSeekService $deepSeekService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private MessageRepository $messageRepository;
    private MessageService $messageService;

    public function __construct(
        DeepSeekService $deepSeekService,
        TelegramBotService $telegramBotService,
        MessageService $messageService,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        LoggerInterface $discussionLogger
    ) {
        $this->deepSeekService = $deepSeekService;
        $this->telegramBotService = $telegramBotService;
        $this->messageService = $messageService;
        //$this->messageRepository = $messageRepository;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->messageRepository = $messageRepository;
        $this->logger = $discussionLogger;
    }

    public function handleMessage(User $user, array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user = $this->userRepository->findByChatId($chatId);
        $this->logger->error('User:' . $user->getUsername() );
        if (!empty($user)) {
            $newMessage = $this->saveMessage($text, $user, Message::USER_ROLE, 'text', $chatId);
            $answer = $this->sendMessageToDeepSeek($user, $newMessage);
        }
        if (!empty($answer) && is_string($answer)) {
            $this->saveMessage($answer, $user, Message::ASSISTANT_ROLE, 'text', $chatId,false, true);
            $this->telegramBotService->sendMessage($chatId, $answer);
        }
    }

    public function sendMessageToDeepSeek(User $user,Message $message): string
    {
        $conversationHistory = $this->messageService->getDeepSeekFormatHistory($user);
        // Основное общение
        $response = $this->deepSeekService->sendChatMessage(
            $conversationHistory,
            $user->getAiRole() ?? 'friend',
            [
                'first_name' => $user->getFirstName(),
                'age' => $user->getAge(),
                'interests' => []
            ]
        );
        //todo VB допиши тут использование токенов

        return $response['content'];
    }

    public function sendSendInitialMessage(User $user, $talkContext = ''): string
    {
        $userContext = $user->getUserContext();
        $role =  $user->getAiRole() ?? 'friend';
        $lastMessage = $user->getLastMessage();

        if (!empty($lastMessage) && $lastMessage->getRole() === Message::ASSISTANT_ROLE){
            $lastMessageText = $lastMessage->getContent();
        }

        $initMessage =  $this->deepSeekService->generateInitiativeMessage(
            $role,
            $userContext,
            $talkContext,
            $lastMessageText ?? ''
        );
        $chatId = $user->getChatId();
        if(!empty($initMessage && is_string($initMessage))) {
            $this->saveMessage($initMessage, $user, Message::ASSISTANT_ROLE, 'text', $chatId, true, true);
            $this->telegramBotService->sendMessage($chatId, $initMessage);
        }
        return $initMessage;
    }

    private function saveMessage(
        string $messageText,
        User $user,
        string $role,
        string $type,
        int $chatId,
        bool $isIniciative = false,
        bool $isProcessed = false
    ): Message {
        $newMessage = new Message();
        $newMessage->setUser($user);
        $newMessage->setContent($messageText);
        $newMessage->setRole($role);
        $newMessage->setMessageType($type);
        $newMessage->setTelegramMessageId($chatId);
        $newMessage->setCreatedAt(date_create_immutable());
        $newMessage->setUpdatedAt(date_create_immutable());
        $newMessage->setIsInitiative($isIniciative);
        $newMessage->setProcessed($isProcessed);

        $this->logger->debug('User:' . $user->getFirstName(). 'Role:' . $role. ' Type:' . $type. ' Message:' . $messageText);

        $this->entityManager->persist($newMessage);
        $this->entityManager->flush();

        return $newMessage;
    }

}
