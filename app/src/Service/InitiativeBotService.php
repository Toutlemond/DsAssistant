<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InitiativeBotService
{
    private DeepSeekService $deepSeekService;
    private TelegramBotService $telegramBotService;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        DeepSeekService        $deepSeekService,
        TelegramBotService     $telegramBotService,
        UserRepository         $userRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface        $initiativeLogger
    )
    {
        $this->deepSeekService = $deepSeekService;
        $this->telegramBotService = $telegramBotService;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->logger = $initiativeLogger;
    }

    public function sendInitiativeMessages(): void
    {
//        $users = $this->userRepository->findActiveUsers();
//
//        foreach ($users as $user) {
//            if ($this->shouldSendInitiativeMessage($user)) {
//                $this->sendMessageToUser($user);
//            }
//        }
    }
}
