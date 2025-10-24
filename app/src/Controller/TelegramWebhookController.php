<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\UserDiscussionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    public function __construct(
        UserRegistrationService $userRegistrationService,
        UserDiscussionService $userDiscussionService,
        LoggerInterface $logger,
        UserRepository $userRepository
    ) {
        $this->userRegistrationService = $userRegistrationService;
        $this->userDiscussionService = $userDiscussionService;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
    }

    #[Route('/webhook/telegram', name: 'telegram_webhook', methods: ['POST','GET'])]
    public function handleWebhook(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent(), true);
            $this->logger->info('Telegram webhook received', ['content' => $content]);

            // Обрабатываем только текстовые сообщения
            if (isset($content['message']['text'])) {
                $message = $content['message'];
                $chatId = $message['chat']['id'];

                $user = $this->userRepository->findByChatId($chatId);
                if (empty($user) || $user->getState() !== UserRegistrationService::COMPLETE_STATE) {
                    $this->userRegistrationService->handleMessage($content['message']);
                } else {
                    $this->userDiscussionService->handleMessage($user,$content['message']);
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
