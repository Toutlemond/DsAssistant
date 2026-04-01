<?php

namespace App\Service;

use App\Entity\Focus;
use App\Entity\Thought;
use App\Repository\ThoughtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InternalThinkingService
{
    public function __construct(
        private FocusManager $focusManager,
        private FocusSourceManager $focusSourceManager,
        private DeepSeekService $deepSeekService,
        private ThoughtRepository $thoughtRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private SelfIdentityService $selfIdentityService
    ) {}

    /**
     * Выполнить один шаг мышления: обработать следующий фокус
     */
    public function thinkOnce(): ?Thought
    {
        $focus = $this->focusManager->getNextFocus();
        if (!$focus) {
            $this->logger->debug('No pending focus');
            return null;
        }

        // Помечаем как processing, чтобы другие процессы не взяли этот же фокус
        $focus->setStatus(Focus::STATUS_PROCESSING);
        $this->entityManager->flush();

        try {
            $thought = $this->processFocus($focus);
            if(empty($thought)){
                $this->logger->debug('No pending thought');
                return null;
            }
            $this->focusManager->markAsDone($focus, $thought->getContent());
            return $thought;
        } catch (\Exception $e) {
            $this->logger->error('Error processing focus', [
                'focus_id' => $focus->getId(),
                'error' => $e->getMessage()
            ]);
            // Возвращаем в pending для повторной попытки
            $this->focusManager->markAsPending($focus);
            return null;
        }
    }

    private function processFocus(Focus $focus): ?Thought
    {
        $prompt = $this->buildPrompt($focus);
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->deepSeekService->makeApiRequest($messages, 0.7); // Низкая температура для точности
        $content = $response['content'] ?? '';

        // Пытаемся распарсить JSON
        $data = json_decode($content, true);

        if (!is_array($data)) {
            $this->logger->error('cant find thought', [
                'content' => $content
            ]);
            return null;
        }

        $thoughtText = $data['thought'] ?? $content;
        $thoughtEssence = $data['thought_essence'] ?? $content;
        $type = $data['type'] ?? $this->determineType($thoughtText);
        $newFocusData = $data['new_focus'] ?? null;

        $thought = new Thought();
        $thought->setUser($focus->getUser());
        $thought->setFocus($focus);
        $thought->setContent($thoughtText);
        $thought->setEssence($thoughtEssence);
        $thought->setType($type);
        $thought->setPrompt($prompt);
        $this->thoughtRepository->save($thought);

        // Создаём новый фокус, если предложен
        if ($newFocusData && !empty($newFocusData['topic'])) {
            $this->focusManager->createFocus(
                topic: $newFocusData['topic'],
                source: 'internal_thought',
                user: $focus->getUser(),
                priority: $newFocusData['priority'] ?? 5,
                context: [
                    'parent_focus_id' => $focus->getId(),
                    'parent_thought_id' => $thought->getId(),
                    'original_response' => $content
                ]
            );
            $this->logger->info('New focus created from thought', [
                'parent_thought_id' => $thought->getId(),
                'new_focus_topic' => $newFocusData['topic']
            ]);
        }

        return $thought;
    }
    private function buildPrompt(Focus $focus): string
    {
        $userInfo = '';
        if ($focus->getUser()) {
            $user = $focus->getUser();
            $userInfo = "Это касается пользователя {$user->getFirstName()} ({$user->getAge()} лет). ";
            $recentThoughts = $this->thoughtRepository->findRecentByUser($user, 3);
            if (!empty($recentThoughts)) {
                $userInfo .= "Недавние мысли о нём:\n";
                foreach ($recentThoughts as $thought) {
                    $userInfo .= "- " . $thought->getContent() . "\n";
                }
            }
        }
        // Убедимся, что у агента есть имя
        $this->selfIdentityService->ensureAgentHasName();
        $agentPersonality = $this->selfIdentityService->getAgentPersonalityContext();



        $context = $focus->getContext() ? json_encode($focus->getContext(), JSON_UNESCAPED_UNICODE) : '';

        return <<<PROMPT
Ты – внутренний голос ИИ-агента. Ты анализируешь информацию о пользователе или другую тему, чтобы лучше понять мир, людей и самого себя.
Сейчас тема для размышления: "{$focus->getTopic()}".
Информация обо мне (ИИ-агенте): {$agentPersonality}
Информация о пользователе (если есть): {$userInfo}
Дополнительный контекст: {$context}

Твоя задача – осмыслить эту тему, используя имеющиеся данные. Не обращайся к пользователю напрямую. Это твой личный монолог.

После размышления верни результат в формате JSON:
{
    "thought": "текст твоей мысли",
    "thought_essence": "Суть мысли в 1 предложении (не более 255 букв)",
    "type": "insight|hypothesis|plan|memory",
    "new_focus": {
        "topic": "тема нового фокуса (если появилась новая интересная тема для размышления, иначе null)",
        "priority": 5
    }
}
Только JSON, без лишних пояснений.
PROMPT;

    }

    private function determineType(string $content): string
    {
        // Простая эвристика: можно улучшить позже
        if (str_contains($content, '?')) {
            return Thought::TYPE_HYPOTHESIS;
        }
        if (preg_match('/надо|нужно|следует|план/i', $content)) {
            return Thought::TYPE_PLAN;
        }
        return Thought::TYPE_INSIGHT;
    }
}
