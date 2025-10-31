<?php

namespace App\Command;

use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\DeepSeekService;
use App\Service\PersonalDataService;
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
    private PersonalDataService $personalDataService;

    public function __construct(
        UserRepository         $userRepository,
        MessageRepository      $messageRepository,
        UserDiscussionService  $userDiscussionService,
        DeepSeekService        $deepSeekService,
        PersonalDataService    $personalDataService,
        EntityManagerInterface $entityManager
    )
    {
        $this->userRepository = $userRepository;
        $this->messageRepository = $messageRepository;
        $this->userDiscussionService = $userDiscussionService;
        $this->deepSeekService = $deepSeekService;
        $this->personalDataService = $personalDataService;
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

        if ($message->isUsedForTasks()) {
            $io->error('Message already used for tasks');
            return Command::INVALID;
        }

        $user = $message->getUser();

        $details = $this->deepSeekService->analyzeMessageForDetails($message->getContent(), $user->getUserContext());

        //$raw = '{"reminders":[{"event":"\u0421\u0430\u043c\u043e\u043b\u0435\u0442 \u0434\u043e \u041f\u0438\u0442\u0435\u0440\u0430","type":"flight","datetime":"2025-11-01 14:00","recurring":false,"priority":"high"},{"event":"\u0420\u0435\u0433\u0430\u0442\u0430 \u043d\u0430 \u043e\u0437\u0435\u0440\u0435 \u0423\u0432\u0438\u043b\u044c\u0434\u044b \u0432 \u0427\u0435\u043b\u044f\u0431\u0438\u043d\u0441\u043a\u0435","type":"personal","datetime":"2025-11-08 00:00","recurring":false,"priority":"medium"}],"future_events":[{"event":"\u0420\u0435\u0433\u0430\u0442\u0430 \u043d\u0430 \u043e\u0437\u0435\u0440\u0435 \u0423\u0432\u0438\u043b\u044c\u0434\u044b \u0432 \u0427\u0435\u043b\u044f\u0431\u0438\u043d\u0441\u043a\u0435","category":"sports","datetime":"2025-11-08 00:00","suggested_mention":"\u0447\u0435\u0440\u0435\u0437 \u043d\u0435\u0434\u0435\u043b\u044e","interest_level":"high"}],"personal_details":{"people":[],"person":[{"name":"\u041d\u0438\u043a\u043e\u043b\u0430\u0439","type":"name","context":"\u0418\u043c\u044f"},{"name":"\u041a\u043e\u043d\u0441\u0442\u0430\u043d\u0442\u0438\u043d\u043e\u0432\u0438\u0447","type":"middlename","context":"\u041e\u0442\u0447\u0435\u0441\u0442\u0432\u043e"}],"pets":[],"user_location":[],"locations":["\u041f\u0438\u0442\u0435\u0440","\u0427\u0435\u043b\u044f\u0431\u0438\u043d\u0441\u043a","\u043e\u0437\u0435\u0440\u043e \u0423\u0432\u0438\u043b\u044c\u0434\u044b"],"preferences":["\u044f\u0445\u0442\u0438\u043d\u0433","\u0440\u0435\u0433\u0430\u0442\u044b"],"important_dates":[]},"past_events":[]}';
        //$details = json_decode($raw,true);

        print_r($details);

        if(!empty($details)) {
            $this->personalDataService->processAnalysisResult($user,$details);
            $message->setUsedForTasks(true);
            $this->entityManager->persist($message);
            $this->entityManager->flush();
        }


        $io->success('Детали получены!');

        return Command::SUCCESS;
    }
}
