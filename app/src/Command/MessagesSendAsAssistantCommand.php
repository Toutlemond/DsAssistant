<?php

namespace App\Command;

use App\Entity\Message;
use App\Repository\UserRepository;
use App\Service\TelegramBotService;
use App\Service\UserDiscussionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'messages:send-as-assistant',
    description: 'Отправит ваще сообщение как будто это сделал асистент',
)]
class MessagesSendAsAssistantCommand extends Command
{
    private UserRepository $userRepository;
    private UserDiscussionService $userDiscussionService;
    private TelegramBotService $telegramBotService;

    public function __construct(UserRepository $userRepository, UserDiscussionService $userDiscussionService,TelegramBotService $telegramBotService )
    {
        $this->userRepository = $userRepository;
        $this->userDiscussionService = $userDiscussionService;
        $this->telegramBotService = $telegramBotService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user_id', InputArgument::REQUIRED, 'User ID')
            ->addArgument('message', InputArgument::REQUIRED, 'Само сообщение')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('user_id');
        $user = $this->userRepository->find($userId);
        if (empty($user)) {
            $io->error('User not found');
            return Command::FAILURE;
        }

        $messageText = $input->getArgument('message');
        if (empty($messageText)) {
            $io->error('message not found');
            return Command::FAILURE;
        }

        if(is_string($messageText)) {
            $this->userDiscussionService->saveMessage($messageText, $user, Message::ASSISTANT_ROLE, 'text', $user->getChatId(), true, true);
            $this->telegramBotService->sendMessage($user->getChatId(), $messageText);
        }

        $io->success('Ваше сообщение отправлено и сохранено как от ассистента');

        return Command::SUCCESS;
    }
}
