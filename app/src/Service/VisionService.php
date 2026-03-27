<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class VisionService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface     $logger,
        private string              $lmStudioUrl = 'http://host.docker.internal:1234',
        private string              $visionModel = 'allenai/olmocr-2-7b'  // название модели в LMStudio
    )
    {
    }

    /**
     * Анализирует изображение и возвращает текстовое описание
     */
    public function analyzeImage(string $imagePath): ?string
    {
        if (!file_exists($imagePath)) {
            $this->logger->warning('Image file not found', ['path' => $imagePath]);
            return null;
        }

        // Читаем и кодируем изображение в base64
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageMime = mime_content_type($imagePath); // например, image/jpeg
// Формируем data URL для вставки в сообщение
        $imageUrl = "data:{$imageMime};base64,{$imageData}";
// Текст запроса (промпт) – можно изменить под вашу задачу
        $prompt = "Опиши, что изображено на этом кадре. Но знай - это кадр с камеры робота и это то на что он смотрит.";
        $payload = [
            'model' => $this->visionModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 300,
            'temperature' => 0.7
        ];

        try {
            $response = $this->httpClient->request('POST', $this->lmStudioUrl . '/v1/chat/completions', [
                'json' => $payload,
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            $description = $data['choices'][0]['message']['content'] ?? null;

            $this->logger->info('Image analyzed', [
                'path' => $imagePath,
                'description' => $description
            ]);

            return $description;

        } catch (\Exception $e) {
            $this->logger->error('Vision API error', [
                'error' => $e->getMessage(),
                'path' => $imagePath
            ]);
            return null;
        }
    }
}
