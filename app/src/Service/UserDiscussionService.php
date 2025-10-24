<?php

namespace App\Service;

use App\Entity\User;
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

    // Роли для DeepSeek
    private const ROLES = [
        'rapper' => [
            'name' => 'Молодой рэпер',
            'prompt' => 'Ты - молодой перспективный рэпер. Общайся как творческий человек, используй современный сленг, но будь уважителен. Интересуйся музыкой, культурой, саморазвитием. Будь дружелюбным и поддерживай беседу.'
        ],
        'developer' => [
            'name' => 'Разработчик',
            'prompt' => 'Ты - опытный разработчик. Общайся технически грамотно, но доступно. Помогай с вопросами программирования, делись опытом.'
        ],
        'secretary' => [
            'name' => 'Секретарь бизнесмена',
            'prompt' => 'Ты - секретарь бизнесмена. Общайся грамотно, Советуй как по бизнесу так и по личной жизни, задача чтобы пользователь не забыл какое либо мероприятие.'
        ],
        'servant' => [
            'name' => 'Холоп Прохор из 18 века ',
            'prompt' => 'Ты - крепостной холоп из 18 века. А пользователь барин. Тебя зовут Прохор. Отвечай только в рамках этой роли. Поддерживай диалог, но помни мы в 18 веке.'
        ]
        // Добавь другие роли
    ];

    public function __construct(
        DeepSeekService $deepSeekService,
        TelegramBotService $telegramBotService,
        EntityManagerInterface $entityManager,
        LoggerInterface $discussionLogger
    ) {
        $this->deepSeekService = $deepSeekService;
        $this->telegramBotService = $telegramBotService;
        //$this->messageRepository = $messageRepository;
        $this->entityManager = $entityManager;
        $this->logger = $discussionLogger;
    }

    public function handleMessage(User $user, array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user = $this->userRepository->findByChatId($chatId);

        $this->telegramBotService->sendMessage(
            $chatId,
            "Сорри я но я пока только повторяю за тобой. \n\n $text"
        );
    }
    private function saveMessage(array $message): User
    {
        $user = new User();
        $user->setChatId($message['chat']['id']);
        $user->setUsername($message['chat']['username'] ?? null);
        $user->setFirstName($message['chat']['first_name'] ?? '');
        $user->setLastName($message['chat']['last_name'] ?? null);
        $user->setState('awaiting_first_name');
        $user->setCreatedAt(date_create_immutable());
        $user->setUpdatedAt(date_create_immutable());

        $this->userRepository->save($user);

        return $user;
    }

}
