<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserRegistrationService
{
    private EntityManagerInterface $entityManager;
    private TelegramBotService $telegramBotService;
    private TelegramTaroBotService $telegramTaroBotService;
    private AbstractTelegramBotService $telegramActualService;
    private UserRepository $userRepository;
    private bool $itsTaroBot = false;
    public CONST WAIT_FIRST_NAME_STATE = 'awaiting_first_name';
    public CONST WAIT_GENDER_STATE =    'awaiting_gender';
    public CONST WAIT_AGE_STATE = 'awaiting_age';
    public CONST WAIT_INTERESTS = 'awaiting_interests';
    public CONST WAIT_MY_ROLE_STATE = 'awaiting_my_role';
    public CONST COMPLETE_STATE = 'completed';

    private const GENDERS = [
        [['text' => '🙋‍♂️ Мужской']],
        [['text' => '🙋‍♀️ Женский']]
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        TelegramBotService $telegramBotService,
        TelegramTaroBotService $telegramTaroBotService,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->telegramBotService = $telegramBotService;
        $this->telegramTaroBotService = $telegramTaroBotService;
        $this->userRepository = $userRepository;
    }

    public function handleMessage(array $message, bool $isTaro = false): void
    {
        $this->telegramActualService = $this->telegramBotService;
        if ($isTaro) {
            $this->itsTaroBot = true;
            $this->telegramActualService = $this->telegramTaroBotService;
        }
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user = $this->userRepository->findByChatId($chatId);

        if (!$user) {
            $this->createNewUser($message);
            $this->askFirstName($chatId);
            return;
        }

        $this->handleUserState($user, $text);
    }

    private function createNewUser(array $message): User
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

    private function handleUserState(User $user, string $text): void
    {
        switch ($user->getState()) {
            case self::WAIT_FIRST_NAME_STATE:
                $this->processFirstName($user, $text);
                break;
            case self::WAIT_GENDER_STATE:
                $this->processGender($user, $text);
                break;
            case self::WAIT_AGE_STATE:
                $this->processAge($user, $text);
                break;
            case self::WAIT_INTERESTS:
                $this->processInterests($user, $text);
                break;
//            case 'awaiting_phone':
//                $this->processPhone($user, $text);
//                break;
            default:
                $this->sendMainMenu($user->getChatId());
                break;
        }
    }

    private function askFirstName(int $chatId): void
    {
        $this->telegramActualService->sendMessage(
            $chatId,
            "Как вас зовут?"
        );
    }

    private function processFirstName(User $user, string $firstName): void
    {
        $user->setFirstName(trim($firstName));
        $user->setState(self::WAIT_GENDER_STATE);
        $this->userRepository->save($user);

        $keyboard = $this->telegramActualService->createReplyKeyboard(self::GENDERS);

        $this->telegramActualService->sendMessage(
            $user->getChatId(),
            "Приятно познакомиться, <b>{$firstName}</b>! Теперь укажи свой пол:",
            $keyboard
        );
    }

    private function processGender(User $user, string $gender): void
    {
        $genderMap = [
            '🙋‍♂️ Мужской' => 'male',
            '🙋‍♀️ Женский' => 'female'
        ];

        if(empty($genderMap[$gender])){
            $keyboard = $this->telegramActualService->createReplyKeyboard(self::GENDERS);

            $this->telegramActualService->sendMessage(
                $user->getChatId(),
                "Возникла ошибка! Укажи свой пол?",
                $keyboard
            );
            return;
        }

        $user->setGender($genderMap[$gender]);
        $user->setState(self::WAIT_AGE_STATE);
        $this->userRepository->save($user);

        $this->telegramActualService->sendMessage(
            $user->getChatId(),
            "Отлично! Теперь скажи, сколько тебе лет?",
            ['remove_keyboard' => true]
        );
    }

    private function processAge(User $user, string $ageText): void
    {
        $age = (int) $ageText;

        if ($age < 5 || $age > 120) {
            $this->telegramActualService->sendMessage(
                $user->getChatId(),
                "Пожалуйста, введи реальный возраст (от 5 до 120 лет):"
            );
            return;
        }

        $user->setAge($age);
        $user->setState(self::WAIT_INTERESTS);
        $this->userRepository->save($user);

        $this->telegramActualService->sendMessage(
            $user->getChatId(),
            "Хорошо! А теперь перечисли то, что тебе интересно - просто несколько слов через запятую или пробел",
            ['remove_keyboard' => true]
        );
    }

    private function processInterests(User $user, string $interestsText): void
    {
        $interestsText = trim($interestsText);
        $interestsText = strip_tags($interestsText);
        $interestsText = str_replace(',', ' ', $interestsText);
        $interestsText = str_replace('-', ' ', $interestsText);
        $interestsText = str_replace('  ', ' ', $interestsText); // да это жесть но мне лень в регулярки
        $interests = explode(' ', $interestsText);

        if(count($interests) > 10){
            $this->telegramActualService->sendMessage(
                $user->getChatId(),
                "Пожалуйста, введи свои интересы,а не Войну и Мир:"
            );
            return;
        }

        $user->setInterests($interests);
        $user->setState(self::COMPLETE_STATE);
        $this->userRepository->save($user);

        $this->sendWelcomeMessage($user);
        //todo vb Разобраться с меню

        if(!$this->itsTaroBot) {
            $this->sendMainMenu($user->getChatId());
        }
    }

    private function sendWelcomeMessage(User $user): void
    {
        $genderText = [
            'male' => 'мужской',
            'female' => 'женский',
            'other' => 'другой'
        ];

        $interests = implode(',', $user->getInterests());
        $message = "🎉 <b>Регистрация завершена!</b>\n\n";
        $message .= "📋 Твои данные:\n";
        $message .= "• Имя: <b>{$user->getFirstName()}</b>\n";
        $message .= "• Пол: <b>{$genderText[$user->getGender()]}</b>\n";
        $message .= "• Возраст: <b>{$user->getAge()}</b>\n";
        $message .= "• Интересы: <b>{$interests}</b>\n\n";
        if($this->itsTaroBot){
            $message .= "Теперь мы можем общаться! Или сразу перейти к гаданию!\n\n";
            $message .= "Давай я представлюсь! Я гадалка Агата, и я могу погадать тебе на картах Таро.\n\n";
            $message .= "Мы можем с тобой просто пообщаться для начала, чтобы я больше о тебе узнала.\n\n";
            $message .= "Но как только ты скажешь <b>погадай</b> я вытяну 3 совершенно случайные карты и дам по ним расклад!\n\n";
            $message .= "После этого мы сможем общаться но следующий расклад я смогу сделать только завтра, Энергия карт на сегодня будет исчерпана.\n\n";
        }else {
            $message .= "Теперь мы можем общаться! Напиши мне что-нибудь 😊";
        }


        $this->telegramActualService->sendMessage($user->getChatId(), $message);
    }

    private function sendMainMenu(int $chatId): void
    {
        $keyboard = $this->telegramActualService->createReplyKeyboard([
            [
                ['text' => 'Профиль'],
                ['text' => 'Настройки']
            ],
            [['text' => 'Помощь']]
        ]);

        $this->telegramActualService->sendMessage(
            $chatId,
            "Так же пришлю главное Главное меню! ",
            $keyboard
        );
    }
}
