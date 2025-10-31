<?php

namespace App\Command;

use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\DeepSeekService;
use App\Service\TelegramBotService;
use App\Service\UserDiscussionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'messages:analize-message-data',
    description: 'Проанализирует одно сообщение и выделит отуда детали пользователя или информацию о напоминаниях ',
)]
class MessagesAnalizeMessageDataCommand extends Command
{
    private UserRepository $userRepository;
    private UserDiscussionService $userDiscussionService;
    private DeepSeekService $deepSeekService;
    private EntityManagerInterface $entityManager;
    private MessageRepository $messageRepository;

    public function __construct(
        UserRepository         $userRepository,
        MessageRepository      $messageRepository,
        UserDiscussionService  $userDiscussionService,
        DeepSeekService        $deepSeekService,
        EntityManagerInterface $entityManager
    )
    {
        $this->userRepository = $userRepository;
        $this->messageRepository = $messageRepository;
        $this->userDiscussionService = $userDiscussionService;
        $this->deepSeekService = $deepSeekService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('message_id', InputArgument::REQUIRED, 'Message ID')
            ->addArgument('set', InputArgument::OPTIONAL, 'Is write to base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $messageId = $input->getArgument('message_id');

        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            $io->error('Message not found');
            return Command::FAILURE;
        }
        $user = $message->getUser();

        $details = $this->deepSeekService->analyzeMessageForDetails($message->getContent(), $user->getUserContext());

        print_r($details);

        $io->success('Детали получены!');

        return Command::SUCCESS;
    }
}
