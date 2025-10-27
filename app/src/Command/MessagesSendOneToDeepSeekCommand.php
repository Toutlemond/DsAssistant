<?php

namespace App\Command;

use App\Entity\Message;
use App\Repository\MessageRepository;
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
    name: 'messages:send-one-to-deep-seek',
    description: 'Отправит сообщение из базы в дипсик, с захватом N прошлых сообщений',
)]
class MessagesSendOneToDeepSeekCommand extends Command
{
    private MessageRepository $messageRepository;
    private UserDiscussionService $userDiscussionService;

    public function __construct(MessageRepository $messageRepository, UserDiscussionService $userDiscussionService )
    {
        $this->messageRepository = $messageRepository;
        $this->userDiscussionService = $userDiscussionService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('messageId', InputArgument::REQUIRED, 'Номер сообщения по базе')
            ->addArgument('count', InputArgument::OPTIONAL, 'Сколько сообщений из базы докинуть')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $messageId = $input->getArgument('messageId');
        $count = $input->getArgument('count');

        if ($count) {
            $io->note(sprintf('You passed an argument: %s', $count));
        }
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            $io->error('Message not found');
            return Command::FAILURE;
        }
        $user = $message->getUser();

        if(empty($user)) {
            $io->error('User not found');
            return Command::FAILURE;
        }
        $response = $this->userDiscussionService->sendMessageToDeepSeek($user,$message);

        $io->success($response);

        return Command::SUCCESS;
    }
}
