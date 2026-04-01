<?php

namespace App\Service;

use App\Entity\Focus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FocusSourceManager
{
    private array $sources = [
        'recent_dialogues',   // анализ последних разговоров
        'random_user',        // случайный пользователь
        'vision',             // изображение с камеры
        'system'              // системные мысли
    ];

    private array $weights = [
        'recent_dialogues' => 40,
        'random_user'      => 30,
        'vision'           => 20,
        'system'           => 10,
    ];

    public function __construct(
        private UserRepository $userRepository,
        private MessageRepository $messageRepository,
        private VisionService $visionService,
        private FocusManager $focusManager,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private PersonalDataService $personalDataService,
    ) {}

    /**
     * Генерирует новый фокус, случайным образом выбирая источник
     */
    public function generate(): ?\App\Entity\Focus
    {
        $source = $this->selectSource();
        $this->logger->debug('Selected focus source', ['source' => $source]);

        $focus = match ($source) {
            'recent_dialogues' => $this->generateFromRecentDialogues(),
            'random_user'      => $this->createUserReflectionFocus(),
            'vision'           => $this->generateFromVision(),
            'system'           => $this->generateSystemFocus(),
            default            => null,
        };

        if ($focus) {
            $this->logger->debug('Focus generated from source', [
                'source' => $source,
                'topic' => $focus->getTopic()
            ]);
        }

        return $focus;
    }

    /**
     * Выбор источника с учётом весов
     */
    private function selectSource(): string
    {
        $total = array_sum($this->weights);
        $rand = rand(1, $total);
        $accum = 0;
        foreach ($this->weights as $source => $weight) {
            $accum += $weight;
            if ($rand <= $accum) {
                return $source;
            }
        }
        return 'system'; // fallback
    }

    /**
     * Анализирует последние диалоги за сегодня
     */
    private function generateFromRecentDialogues(): ?\App\Entity\Focus
    {
        $today = new \DateTime('today');
        $messages = $this->messageRepository->findMessagesSince($today);
        if (empty($messages)) {
            return null;
        }

        // Группируем сообщения по пользователям
        $usersMessages = [];
        foreach ($messages as $msg) {
            $user = $msg->getUser();
            if (!$user) continue;
            $usersMessages[$user->getId()]['user'] = $user;
            $usersMessages[$user->getId()]['messages'][] = $msg->getContent();
        }

        if (empty($usersMessages)) {
            return null;
        }

        // Выбираем пользователя с наибольшим количеством сообщений сегодня
        usort($usersMessages, fn($a, $b) => count($b['messages']) <=> count($a['messages']));
        $topUser = $usersMessages[0]['user'];
        $messageCount = count($usersMessages[0]['messages']);

        $topic = "Проанализируй общение с пользователем {$topUser->getFirstName()} за сегодня (всего сообщений: {$messageCount}). "
            . "Что нового я узнал? Что его волнует? Стоит ли о чём-то спросить в следующий раз?";

        // Используем FocusManager для создания
        return $this->focusManager->createFocus(
            topic: $topic,
            source: 'system_auto',
            user: $topUser,
            priority: 7,
            context: ['message_count' => $messageCount]
        );
    }

    /**
     * Случайный пользователь
     */
    private function generateFromRandomUser(): ?\App\Entity\Focus
    {
        $users = $this->userRepository->findAll();
        if (empty($users)) {
            return null;
        }

        $user = $users[array_rand($users)];
        $topic = "Подумай о пользователе {$user->getFirstName()}. Что я о нём знаю? Как проходило наше общение? Что его волнует?";

        return $this->focusManager->createFocus(
            topic: $topic,
            source: 'system_auto',
            user: $user,
            priority: 6,
            context: ['user_last_message' => $user->getLastMessage()->getContent()]
        );
    }


    private function createUserReflectionFocus(): ?Focus
    {
        $activeUsers = $this->userRepository->findActiveSince(new \DateTime('-7 days'));
        if (empty($activeUsers)) return null;

        $user = $activeUsers[array_rand($activeUsers)];
        $userContext = $this->personalDataService->getUserContext($user);

        $topic = "Подумай о пользователе {$user->getFirstName()}. Вот что я о нём знаю:\n{$userContext}\n\nЧто я могу из этого извлечь? Какой у него характер? Что его волнует? Как я могу улучшить общение с ним?";

        return $this->focusManager->createFocus($topic, 'system_auto', $user, 6, ['context' => $userContext]);
    }

    /**
     * Vision: анализируем последнее изображение
     */
    private function generateFromVision(): ?\App\Entity\Focus
    {
        $imagePath = $this->findLatestImage();
        if (!$imagePath) {
            return null;
        }

        $description = $this->visionService->analyzeImage($imagePath);
        if (!$description) {
            return null;
        }

        $topic = "Я вижу: {$description}. О чём это мне говорит? Может, это как-то связано с моим окружением?";

        return $this->focusManager->createFocus(
            topic: $topic,
            source: 'vision_auto',
            user: null,
            priority: 5,
            context: ['image_path' => $imagePath, 'description' => $description]
        );
    }

    /**
     * Системные мысли (здоровье, загрузка CPU и т.п.)
     */
    private function generateSystemFocus(): ?\App\Entity\Focus
    {
        $topics = [
            "Как я себя чувствую сегодня? Стоит ли сделать перерыв?",
            "Загрузка процессора: {cpu}%. Нормально ли это? Может, нужно что-то оптимизировать?",
            "Какие у меня сейчас цели? Достигаю ли я их?",
            "Что мне нравится в общении с людьми? Что я могу улучшить?",
            "Какой вопрос я хотел бы задать сам себе?",
        ];

        // Можно подставить реальные данные о системе
        $cpuLoad = sys_getloadavg()[0] ?? 0;
        $topic = str_replace('{cpu}', round($cpuLoad, 1), $topics[array_rand($topics)]);

        return $this->focusManager->createFocus(
            topic: $topic,
            source: 'system_auto',
            user: null,
            priority: 3
        );
    }

    /**
     * Поиск самого свежего изображения в папке
     */
    private function findLatestImage(): ?string
    {
        $dir = __DIR__;
        $imageFolder = $dir.'/../../public/pict/vision/';
        if (!is_dir($imageFolder)) {
            mkdir($imageFolder, 0755, true);
        }

        $files = glob($imageFolder . '/*.{jpg,jpeg,png}', GLOB_BRACE);
        if (empty($files)) {
            return null;
        }

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        return $files[0];
    }
}
