<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\BotMenuService;
use App\Service\UserDiscussionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\UserRegistrationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;


final class TelegramWebhookController extends AbstractController
{

    private UserRegistrationService $userRegistrationService;
    private LoggerInterface $logger;
    private UserRepository $userRepository;
    private UserDiscussionService $userDiscussionService;
    private BotMenuService $botMenuService;

    public function __construct(
        UserRegistrationService $userRegistrationService,
        UserDiscussionService $userDiscussionService,
        BotMenuService $botMenuService,
        LoggerInterface $logger,
        UserRepository $userRepository
    ) {
        $this->userRegistrationService = $userRegistrationService;
        $this->userDiscussionService = $userDiscussionService;
        $this->botMenuService = $botMenuService;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
    }

    #[Route('/webhook/telegram', name: 'telegram_webhook', methods: ['POST','GET'])]
    public function handleWebhook(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent(), true);
            $this->logger->info('Telegram webhook received', ['content' => $content]);

            // ⭐⭐ ВАЖНО: Добавляем обработку callback_query ⭐⭐
            if (isset($content['callback_query'])) {
                $callbackQuery = $content['callback_query'];
                $chatId = $callbackQuery['message']['chat']['id'];
                $callbackData = $callbackQuery['data'];
                $callbackId = $callbackQuery['id'];

                $this->logger->info('Callback button pressed', [
                    'chatId' => $chatId,
                    'callbackData' => $callbackData,
                    'callbackId' => $callbackId
                ]);

                // Обрабатываем нажатие кнопки
                $this->botMenuService->handleCallbackQuery($callbackQuery);

                // Отвечаем на callback (обязательно!)
                return new JsonResponse(['method' => 'answerCallbackQuery', 'callback_query_id' => $callbackId]);
            }


            // Обрабатываем текстовые сообщения
            if (isset($content['message']['text'])) {
                $message = $content['message'];
                $chatId = $message['chat']['id'];
                $text = $message['text'];

                $user = $this->userRepository->findByChatId($chatId);
                if (empty($user) || $user->getState() !== UserRegistrationService::COMPLETE_STATE) {
                    $this->userRegistrationService->handleMessage($content['message']);
                } else {
                    if(in_array($text,['Профиль','Настройки','Помощь'])){
                        $this->botMenuService->handleMessage($user, $content['message']);
                    } else {
                        $this->userDiscussionService->handleMessage($user, $content['message']);
                    }
                }
            }

            return new Response('OK');

        } catch (\Exception $e) {
            $this->logger->error('Webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new Response('ERROR', 500);
        }
    }

    #[Route('/webhook/telegram/setup', name: 'telegram_webhook_setup', methods: ['GET'])]
    public function setupWebhook(): Response
    {
        return $this->json([
            'message' => 'Use php bin/console app:set-web-hook to setup webhook'
        ]);
    }
}
