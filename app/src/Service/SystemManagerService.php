<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SystemManagerService
{

    private const SYSTEM_USER_ID = 1;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private LoggerInterface $logger;


    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        LoggerInterface $systemLogger
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->logger = $systemLogger;
    }

    public function useTokens($response): bool
    {
        $user = $this->userRepository->find(self::SYSTEM_USER_ID);
        if ($user) {
            // списываем токены на систему
            if ($response['usage']) {
                $user->addTokenUsage(
                    $response['usage']['prompt_tokens'],
                    $response['usage']['completion_tokens']
                );
            }
            $this->entityManager->flush();
            return true;
        }
        return false;
    }
}
