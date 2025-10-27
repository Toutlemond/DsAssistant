<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\UserDiscussionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'messages:send-init-message',
    description: 'Отправит инициативную команду юзеру',
)]
class MessagesSendInitMessageCommand extends Command
{
    private UserRepository $userRepository;
    private UserDiscussionService $userDiscussionService;

    public function __construct(UserRepository $userRepository, UserDiscussionService $userDiscussionService )
    {
        $this->userRepository = $userRepository;
        $this->userDiscussionService = $userDiscussionService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'User id')
            ->addArgument('context', InputArgument::OPTIONAL, 'context')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');
        $context = $input->getArgument('context');
        if(empty($context)){
            $context = '';
        }

        $user = $this->userRepository->find($userId);
        if (empty($user)) {
            $io->error('User not found');
            return Command::FAILURE;
        }

        $response = $this->userDiscussionService->sendSendInitialMessage($user, $context);

        $io->success('Init message to user ' .$user->getId() .  '-' . $user->getUsername() . ' sent');

        return Command::SUCCESS;
    }
}
