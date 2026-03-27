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
        private DeepSeekService $deepSeekService,
        private ThoughtRepository $thoughtRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
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

    private function processFocus(Focus $focus): Thought
    {
        // Формируем промпт для DeepSeek в зависимости от источника и контекста
        $prompt = $this->buildPrompt($focus);

        // Получаем ответ от DeepSeek (используем существующий сервис)

        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->deepSeekService->makeApiRequest($messages, 0.7); // Низкая температура для точности


        // Создаём мысль
        $thought = new Thought();
        $thought->setUser($focus->getUser());
        $thought->setFocus($focus);
        $thought->setPrompt($prompt);
        $thought->setContent($response['content'] ?? '');
        $thought->setType($this->determineType($response['content'] ?? ''));

        $this->thoughtRepository->save($thought);

        // Здесь можно создать новые фокусы на основе ответа (будущее расширение)

        return $thought;
    }

    private function buildPrompt(Focus $focus): string
    {
        $userInfo = '';
        if ($focus->getUser()) {
            $user = $focus->getUser();
            $userInfo = "Это касается пользователя {$user->getFirstName()} ({$user->getAge()} лет). ";
            // Можно добавить последние мысли о пользователе
            $recentThoughts = $this->thoughtRepository->findRecentByUser($user, 3);
            if (!empty($recentThoughts)) {
                $userInfo .= "Недавние мысли о нём:\n";
                foreach ($recentThoughts as $thought) {
                    $userInfo .= "- " . $thought->getContent() . "\n";
                }
            }
        }

        $context = $focus->getContext() ? json_encode($focus->getContext(), JSON_UNESCAPED_UNICODE) : '';

        return <<<PROMPT
Ты – внутренний голос. Ты размышляешь над следующей темой: "{$focus->getTopic()}".
Источник фокуса: {$focus->getSource()}.
{$userInfo}
Дополнительный контекст: {$context}

Подумай об этом и запиши свои мысли. Будь естественным, как будто разговариваешь сам с собой. Не нужно обращаться к пользователю напрямую.
Твоя мысль:
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
