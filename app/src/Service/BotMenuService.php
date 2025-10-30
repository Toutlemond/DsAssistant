<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class BotMenuService
{
    private TelegramBotService $telegramBotService;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TelegramBotService $telegramBotService,
        UserRepository $userRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->telegramBotService = $telegramBotService;
        $this->userRepository = $userRepository;
    }
    public function handleMessage(User $user, array $message)
    {
        $text = $message['text'] ?? '';
        if ($text === 'Профиль') {
            $this->sendProfile($user);
        } elseif ($text === 'Настройки'){
            $this->sendSettings($user);
        } elseif ($text === 'Помощь'){
            $this->sendHelp($user);
        }
    }

    public function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackData = $callbackQuery['data'];
        $chatId = $callbackQuery['message']['chat']['id'];

        // Разбираем callback_data
        switch ($callbackData) {
            case 'action:profile:name':
                $responseText = "Укажите имя";
                break;
            case 'action:profile:gender':
                $responseText = "Укажите пол";
                break;
            case 'action:profile:interests':
                $responseText = "Перечислите интересы через запятую";
                break;
            case 'action:profile:age':
                $responseText = "Укажите возраст";
                break;
            case 'action:setrole:oldfriend':
                $responseText = $this->setRole($chatId,'oldfriend');
                break;
            case 'action:setrole:rapper':
                $responseText = $this->setRole($chatId,'rapper');
                break;
            case 'action:setrole:friend':
                $responseText = $this->setRole($chatId,'friend');
                break;
            case 'action:setrole:developer':
                $responseText = $this->setRole($chatId,'developer');
                break;
            case 'action:setrole:secretary':
                $responseText = $this->setRole($chatId,'secretary');
                break;
            case 'action:setrole:servant':
                $responseText = $this->setRole($chatId,'servant');
                break;
            case 'action:setrole:psychologist':
                $responseText = $this->setRole($chatId,'psychologist');
                break;
             case 'action:setrole:trickster':
                $responseText = $this->setRole($chatId,'trickster');
                break;
            default:
                $responseText = "❌ Неизвестная команда";
        }

        // Отправляем ответ (можно также редактировать сообщение)
        $this->telegramBotService->sendMessage($chatId, $responseText);
    }

    private function setRole(int $chatId, $role): string {
        $user = $this->userRepository->findByChatId($chatId);
        if (empty($user)) {
            return 'error - User not found';
        }
        $user->setAiRole($role);
        $this->userRepository->save($user);
        $roleName = DeepSeekService::ROLES[$role];
        return 'Отлично! Теперь роль: '. $roleName['name'];
    }

    private function sendHelp(User $user)
    {
        $ver = '0.3A';
        $this->telegramBotService->sendMessage(
            $user->getChatId(),
            "Это бот Ассистент. Версия ".$ver
        );
    }

    private function sendProfile(User $user)
    {
        $genderText = [
            'male' => 'мужской',
            'female' => 'женский'
        ];
        $interests = implode(',', $user->getInterests());
        $message = "Твои данные:\n";
        $message .= "• Имя: <b>{$user->getFirstName()}</b>\n";
        $message .= "• Пол: <b>{$genderText[$user->getGender()]}</b>\n";
        $message .= "• Возраст: <b>{$user->getAge()}</b>\n";
        $message .= "• Интересы: <b>{$interests}</b>\n\n";
        $message .= "Хотите чтото поменять?";

        $keyboard = $this->telegramBotService->createInlineKeyboard([
            [
                ['text' => 'Поменять имя','callback_data'=>'action:profile:name'],

            ],
            [
                ['text' => 'Поменять пол','callback_data'=>'action:profile:gender'],
            ],
            [
                ['text' => 'Поменять возраст','callback_data'=>'action:profile:age'],
            ],
            [
                ['text' => 'Поменять Интересы','callback_data'=>'action:profile:interests']
            ]
        ]);

        $this->telegramBotService->sendMessage($user->getChatId(), $message, $keyboard);
    }

    private function sendSettings(User $user)
    {
        $aiRole = $user->getAiRole();
        if (empty($aiRole)) {
            $aiRole = 'friend';
        }
        if (!empty(DeepSeekService::ROLES[$aiRole])) {
            $roleName = DeepSeekService::ROLES[$aiRole];
        } else {
            $roleName = [];
            $roleName['name'] = 'Друг';
        }

        $keyboardArray =[];

        foreach (DeepSeekService::ROLES as $roleKey => $roleValue) {
            $keyboardArray[] =  [
                ['text' => $roleValue['name'],'callback_data'=>'action:setrole:'.$roleKey],
            ];
        }
        $keyboard = $this->telegramBotService->createInlineKeyboard($keyboardArray);
        $this->telegramBotService->sendMessage(
            $user->getChatId(),
            "Ваш Ассистент сейчас: " . $roleName['name'] . "\n" .
            "Хотите сменять роль Ассистента? " . "\n",
            $keyboard
        );
    }
}
