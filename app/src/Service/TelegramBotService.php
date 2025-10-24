<?php

namespace App\Service;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramBotService
{
    private HttpClientInterface $httpClient;
    private string $botToken;
    private string $webhookUrl;
    private Telegram $telegram;

    public function __construct(HttpClientInterface $httpClient, string $botToken, string $webhookUrl)
    {
        $this->httpClient = $httpClient;
        $this->botToken = $botToken;
        $this->webhookUrl = $webhookUrl;
        $this->telegram = new Telegram($this->botToken, 'DsAssistantBot');
    }

    public function setWebhook(): string
    {
        try {
            // Create Telegram API object
            $telegram = new Telegram($this->botToken, 'DsAssistantBot'); //TODO vb Переделай на env

            // Set webhook
            $result = $telegram->setWebhook($this->webhookUrl);

            if ($result->isOk()) {
                return $result->getDescription();
            } else {
                return "Error setting webhook: " . $result->getDescription();
            }
        } catch (TelegramException $e) {
            return "Telegram Exception: " . $e->getMessage();
        }
    }

    public function deleteWebhook(): array
    {
        $response = $this->httpClient->request('GET', "https://api.telegram.org/bot{$this->botToken}/deleteWebhook");

        return $response->toArray();
    }

    public function sendMessage(int $chatId, string $text, array $replyMarkup = null): array
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }


        $response = $this->httpClient->request('POST', "https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'json' => $data
        ]);

        return $response->toArray();
    }

    public function createReplyKeyboard(array $buttons, bool $resize = true, bool $oneTime = false): array
    {
        return [
            'keyboard' => $buttons,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $oneTime
        ];
    }

    public function createInlineKeyboard(array $buttons): array
    {
        return [
            'inline_keyboard' => $buttons
        ];
    }
}
