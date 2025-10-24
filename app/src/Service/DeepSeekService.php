<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeepSeekService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;

    private const API_URL = 'https://api.deepseek.com/v1/chat/completions';

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $deepseekLogger,
        string $deepseekApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $deepseekLogger;
        $this->apiKey = $deepseekApiKey;
    }

    public function sendMessage(array $messages, float $temperature = 0.7): string
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => 2000,
                ],
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'];

            $this->logger->info('DeepSeek API response', [
                'input_messages' => count($messages),
                'response_length' => strlen($content),
                'usage' => $data['usage'] ?? []
            ]);

            return $content;

        } catch (\Exception $e) {
            $this->logger->error('DeepSeek API error', ['error' => $e->getMessage()]);
            return 'Извините, произошла ошибка. Попробуйте позже.';
        }
    }

    public function analyzeUserProfile(User $user, array $recentMessages): array
    {
        $prompt = $this->buildProfileAnalysisPrompt($user, $recentMessages);

        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => "Проанализируй последние сообщения пользователя и верни JSON с анализом."]
        ];

        $response = $this->sendMessage($messages, 0.3);

        return $this->parseProfileAnalysis($response);
    }

    private function buildProfileAnalysisPrompt(User $user, array $recentMessages): string
    {
        $conversation = implode("\n", array_slice($recentMessages, -10)); // последние 10 сообщений

        return "Ты - аналитик личности. Проанализируй сообщения пользователя и верни JSON:
        {
            \"interests\": [\"array\", \"of\", \"key\", \"interests\"],
            \"mood\": \"current_mood\",
            \"topics\": [\"discussed\", \"topics\"],
            \"personality_traits\": [\"observed\", \"traits\"],
            \"conversation_style\": \"formal/casual/etc\",
            \"engagement_level\": \"high/medium/low\"
        }

        Пользователь: {$user->getFirstName()}, {$user->getAge()} лет
        Последние сообщения:
        {$conversation}";
    }
}
