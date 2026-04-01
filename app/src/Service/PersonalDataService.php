<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\PersonalData;
use App\Entity\PastEventsTask;
use App\Entity\MessageSendTask;
use App\Repository\MessageRepository;
use App\Repository\PersonalDataRepository;
use App\Repository\PastEventsTaskRepository;
use Doctrine\ORM\EntityManagerInterface;

class PersonalDataService
{
    public function __construct(
        private PersonalDataRepository $personalDataRepository,
        private PastEventsTaskRepository $pastEventsTaskRepository,
        private MessageTaskService $messageTaskService,
        private EntityManagerInterface $entityManager,
        private  MessageRepository $messageRepository,
    ) {}

    public function processAnalysisResult(User $user, array $analysisResult): void
    {
        // 1. НАПОМИНАНИЯ - сразу создаем MessageSendTask
        foreach ($analysisResult['reminders'] as $reminder) {
            $this->createReminderTask($user, $reminder);
        }

        // 2. БУДУЩИЕ СОБЫТИЯ - сохраняем в PastEventsTask для отложенной обработки
        foreach ($analysisResult['future_events'] as $event) {
            $this->createPastEventTask($user, $event);
        }

        // 3. ЛИЧНЫЕ ДАННЫЕ - сохраняем в PersonalData
        $this->updatePersonalDetails($user, $analysisResult['personal_details']);

        $this->entityManager->flush();
    }

    private function createReminderTask(User $user, array $reminderData): void
    {
        if (empty($reminderData['datetime'])) {
            return; // Пропускаем напоминания без даты
        }

        $reminderDate = new \DateTime($reminderData['datetime']);
        $now = new \DateTime();

        // Создаем задачу только если напоминание в будущем
        if ($reminderDate > $now) {
            $message = $this->buildReminderMessage($reminderData);

            $this->messageTaskService->createTask(
                $user,
                $message,
                \DateTimeImmutable::createFromInterface($reminderDate)
            );

            $this->entityManager->flush();
        }
    }

    private function buildReminderMessage(array $reminderData): string
    {
        $event = $reminderData['event'];
        $type = $reminderData['type'] ?? 'напоминание';

        $messages = [
            "⏰ Напоминаю: {$event}",
            "🔔 Не забудь: {$event}",
            "📅 Сегодня: {$event}",
            "👋 Привет! Напоминаю о: {$event}"
        ];

        return $messages[array_rand($messages)];
    }

    private function createPastEventTask(User $user, array $eventData): void
    {
        // Проверяем, нет ли уже такой задачи
        $existing = $this->pastEventsTaskRepository->findOneBy([
            'user' => $user,
            'event' => $eventData['event'],
            'isProcessed' => false
        ]);

        if (!$existing) {
            $task = new PastEventsTask();
            $task->setUser($user);
            $task->setEvent($eventData['event']);
            $task->setCategory($eventData['category'] ?? 'entertainment');
            $task->setInterestLevel($eventData['interest_level'] ?? 'medium');
            $task->setOriginalContext(json_encode($eventData, JSON_UNESCAPED_UNICODE));

            $this->entityManager->persist($task);
        }
    }

    private function updatePersonalDetails(User $user, array $personalDetails): void
    {
        // Сохраняем людей
        foreach ($personalDetails['people'] ?? [] as $person) {
            $this->savePersonalDetail($user, 'person', $person['name'], $person);
        }

        // Сохраняем животных
        foreach ($personalDetails['pets'] ?? [] as $pet) {
            $this->savePersonalDetail($user, 'pet', $pet['name'], $pet);
        }

        // Сохраняем места

        foreach ($personalDetails['locations'] ?? [] as $location) {
           $this->savePersonalDetail($user, 'location', $location, ['name' => $location]);
        }

        // Сохраняем предпочтения
        foreach ($personalDetails['preferences'] ?? [] as $preference) {
            $this->savePersonalDetail($user, 'preference', $preference, ['value' => $preference]);
        }

        // Сохраняем важные даты
        foreach ($personalDetails['important_dates'] ?? [] as $date) {
            $this->savePersonalDetail($user, 'important_date', $date['event'], $date);
        }

        // Сохраняем данные о персоне
        foreach ($personalDetails['person'] ?? [] as $person) {
            if(!empty($person['type']) && !empty($person['name'])) {
                $this->savePersonalDetail($user, 'person', $person['type'], $person);
            }
        }
    }

    private function savePersonalDetail(User $user, string $type, string $data_key, array $data): void
    {
        $existing = $this->personalDataRepository->findOneBy([
            'user' => $user,
            'type' => $type,
            'data_key' => $data_key
        ]);
$val = json_encode($data, JSON_UNESCAPED_UNICODE);
print_r($val);
        if (!$existing) {
            $detail = new PersonalData();
            $detail->setUser($user);
            $detail->setType($type);
            $detail->setDataKey($data_key);
            $detail->setValue($val);

            $this->entityManager->persist($detail);
        }
    }

    /**
     * Обрабатывает PastEventsTask и создает MessageSendTask для подходящих событий
     */
    public function processPastEvents(): int
    {
        $tasks = $this->pastEventsTaskRepository->findReadyForProcessing();
        $processedCount = 0;

        foreach ($tasks as $task) {
            if ($this->shouldCreateReminderForEvent($task)) {
                $this->createEventReminder($task);
                $task->markAsProcessed();
                $processedCount++;
            }
        }

        $this->entityManager->flush();
        return $processedCount;
    }

    private function shouldCreateReminderForEvent(PastEventsTask $task): bool
    {
        $now = new \DateTime();
        return $task->getSuggestedRemindAt() <= $now && !$task->isProcessed();
    }

    private function createEventReminder(PastEventsTask $task): void
    {
        $message = $this->buildEventReminderMessage($task);
        $remindAt = new \DateTime('+1 hour'); // Напоминаем через час

        $this->messageTaskService->createTask(
            $task->getUser(),
            $message,
            \DateTimeImmutable::createFromInterface($remindAt)
        );
    }

    private function buildEventReminderMessage(PastEventsTask $task): string
    {
        $event = $task->getEvent();

        $messages = [
            "👋 Помнишь, ты рассказывал про '{$event}'? Как насчет повторить?",
            "🎯 Я вспомнил про '{$event}' - может быть, стоит снова этим заняться?",
            "💭 Кстати, о '{$event}' - все еще интересует?",
            "🔍 Напомнил себе про '{$event}' - может, обсудим?"
        ];

        return $messages[array_rand($messages)];
    }

    public function getUserContext(User $user, int $limitMessages = 10): string
    {
        $context = "Пользователь: {$user->getFirstName()}, {$user->getAge()} лет\n";

        // Личные данные (из personal_data)
        $personalData = $this->personalDataRepository->findByUser($user);
        $people = [];
        $pets = [];
        $locations = [];
        $preferences = [];
        foreach ($personalData as $data) {
            if ($data->getType() === 'person') {
                $people[] = $data->getDataKey();
            } elseif ($data->getType() === 'pet') {
                $pets[] = $data->getDataKey();
            } elseif ($data->getType() === 'location') {
                $locations[] = $data->getDataKey();
            } elseif ($data->getType() === 'preference') {
                $preferences[] = $data->getDataKey();
            }
        }
        if (!empty($people)) $context .= "Близкие люди: " . implode(', ', $people) . "\n";
        if (!empty($pets)) $context .= "Питомцы: " . implode(', ', $pets) . "\n";
        if (!empty($locations)) $context .= "Места: " . implode(', ', $locations) . "\n";
        if (!empty($preferences)) $context .= "Предпочтения: " . implode(', ', $preferences) . "\n";

        // Последние сообщения
        $messages = $this->messageRepository->findRecentUserMessages($user->getId(), $limitMessages);
        if (!empty($messages)) {
            $context .= "\nНедавние сообщения пользователя:\n";
            foreach ($messages as $msg) {
                $context .= "- " . $msg->getContent() . "\n";
            }
        }

        // Предыдущие мысли агента о пользователе
        $thoughts = $this->messageRepository->findRecentByUser($user, 5);
        if (!empty($thoughts)) {
            $context .= "\nМои предыдущие мысли о нём:\n";
            foreach ($thoughts as $thought) {
                $context .= "- " . $thought->getContent() . "\n";
            }
        }

        return $context;
    }

}
