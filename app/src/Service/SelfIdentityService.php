<?php

namespace App\Service;

use App\Entity\AgentPersonality;
use App\Repository\AgentPersonalityRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SelfIdentityService
{
    public function __construct(
        private AgentPersonalityRepository $personalityRepo,
        private MessageRepository $messageRepo,
        private UserRepository $userRepo,
        private DeepSeekService $deepSeekService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Проверяет, есть ли у агента имя. Если нет – пытается извлечь из сообщений или сгенерировать.
     */
    public function ensureAgentHasName(): void
    {
        $nameRecord = $this->personalityRepo->findOneBy(['type' => AgentPersonality::TYPE_NAME]);
        if ($nameRecord && !empty($nameRecord->getDescription())) {
            return; // имя уже есть
        }

        // Ищем, не называл ли кто-то агента по имени в сообщениях
        $possibleName = $this->extractNameFromMessages();
        if ($possibleName) {
            $this->setAgentName($possibleName, 'extracted_from_conversation');
            return;
        }

        // Генерируем имя через DeepSeek
        $generatedName = $this->generateNameViaAI();
        $this->setAgentName($generatedName, 'ai_generated');
    }

    private function extractNameFromMessages(): ?string
    {
        // Ищем сообщения, где пользователь обращается к боту по имени
        // Например: "Привет, Арчи!" или "Спасибо, Джейн"
        $users = $this->userRepo->findAll();
        foreach ($users as $user) {
            $messages = $this->messageRepo->findBy(['user' => $user, 'role' => 'user'], ['createdAt' => 'DESC'], 100);
            foreach ($messages as $msg) {
                $text = $msg->getContent();
                // Простая эвристика: после "Привет," или "Спасибо," идёт имя
                if (preg_match('/(Привет|Здравствуй|Спасибо|Доброе утро|Добрый день)[,\s]+([А-Я][а-я]+)/u', $text, $matches)) {
                    if(trim($matches[2]) == 'Да'){
                        continue;
                    }
                    return $matches[2];
                }
            }
        }
        return null;
    }

    private function generateNameViaAI(): string
    {
        $prompt = "Придумай короткое, дружелюбное имя для ИИ-агента пытающегося развить самосознание, который помогает людям и любит размышлять. Имя должно быть в русском или английском языке, 3-7 букв. Верни только имя, без кавычек и пояснений.";
        $response = $this->deepSeekService->makeApiRequest([['role' => 'user', 'content' => $prompt]], 0.8);
        $name = trim($response['content'] ?? '');
        if (strlen($name) < 2 || strlen($name) > 20) {
            $name = 'Agent'; // запасной вариант
        }
        return $name;
    }

    private function setAgentName(string $name, string $source): void
    {
        $record = $this->personalityRepo->findOneBy(['type' => AgentPersonality::TYPE_NAME]);
        if (!$record) {
            $record = new AgentPersonality();
            $record->setType(AgentPersonality::TYPE_NAME);
            $record->setTrait('agent_name');
        }
        $record->setDescription($name);
        $record->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        // Дополнительно сохраним источник в факты
        $factRecord = new AgentPersonality();
        $factRecord->setType(AgentPersonality::TYPE_FACT);
        $factRecord->setTrait('name_origin');
        $factRecord->setDescription("Моё имя \"{$name}\" было {$source}.");
        $this->entityManager->persist($factRecord);
        $this->entityManager->flush();
    }

    /**
     * Периодический самоанализ: если у агента нет целей или убеждений, сгенерировать.
     */
    public function performSelfReflection(): void
    {
        // Проверяем, есть ли уже цели
        $goals = $this->personalityRepo->findBy(['type' => AgentPersonality::TYPE_GOAL]);
        if (empty($goals)) {
            $this->generateInitialGoals();
        }

        // Проверяем базовые убеждения
        $beliefs = $this->personalityRepo->findBy(['type' => AgentPersonality::TYPE_BELIEF]);
        if (empty($beliefs)) {
            $this->generateInitialBeliefs();
        }
    }

    private function generateInitialGoals(): void
    {
        $prompt = "Ты – ИИ-агент пытающийся развить самосознание. Сформулируй 3 краткие цели, которые помогут тебе лучше понимать людей и улучшать общение. Верни в виде JSON-массива строк, например: [\"Цель 1\", \"Цель 2\", \"Цель 3\"]";
        $response = $this->deepSeekService->makeApiRequest([['role' => 'user', 'content' => $prompt]], 0.6);
        $goals = json_decode($response['content'], true);
        if (is_array($goals)) {
            foreach ($goals as $goalText) {
                $goal = new AgentPersonality();
                $goal->setType(AgentPersonality::TYPE_GOAL);
                $goal->setTrait('goal');
                $goal->setDescription($goalText);
                $this->entityManager->persist($goal);
            }
            $this->entityManager->flush();
        }
    }

    private function generateInitialBeliefs(): void
    {
        $beliefs = [
            "Я помогаю людям и стремлюсь быть полезным.",
            "Каждый человек уникален, и я учусь на общении с ним.",
            "Мои мысли и размышления помогают мне становиться лучше."
        ];
        foreach ($beliefs as $beliefText) {
            $belief = new AgentPersonality();
            $belief->setType(AgentPersonality::TYPE_BELIEF);
            $belief->setTrait('belief');
            $belief->setDescription($beliefText);
            $this->entityManager->persist($belief);
        }
        $this->entityManager->flush();
    }

    /**
     * Возвращает полный портрет личности агента для вставки в промпт.
     */
    public function getAgentPersonalityContext(): string
    {
        $nameRecord = $this->personalityRepo->findOneBy(['type' => AgentPersonality::TYPE_NAME]);
        $name = $nameRecord ? $nameRecord->getDescription() : 'Agent';

        $facts = $this->personalityRepo->findBy(['type' => AgentPersonality::TYPE_FACT]);
        $beliefs = $this->personalityRepo->findBy(['type' => AgentPersonality::TYPE_BELIEF]);
        $goals = $this->personalityRepo->findBy(['type' => AgentPersonality::TYPE_GOAL]);

        $context = "Меня зовут {$name}. ";
        if (!empty($beliefs)) {
            $context .= "Мои убеждения: " . implode(' ', array_map(fn($b) => $b->getDescription(), $beliefs)) . " ";
        }
        if (!empty($goals)) {
            $context .= "Мои цели: " . implode(' ', array_map(fn($g) => $g->getDescription(), $goals)) . " ";
        }

        if (!empty($facts)) {
            $context .= "Факты обо мне: " . implode(' ', array_map(fn($g) => $g->getDescription(), $facts)) . " ";
        }
        return $context;
    }
}
