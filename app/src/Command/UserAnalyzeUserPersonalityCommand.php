<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\DeepSeekService;
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
    name: 'user:analyze-user-personality',
    description: 'Add a short description for your command',
)]
class UserAnalyzeUserPersonalityCommand extends Command
{
    private UserRepository $userRepository;
    private UserDiscussionService $userDiscussionService;
    private DeepSeekService $deepSeekService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserDiscussionService $userDiscussionService,
        DeepSeekService $deepSeekService,
        EntityManagerInterface $entityManager
) {
        $this->userRepository = $userRepository;
        $this->userDiscussionService = $userDiscussionService;
        $this->deepSeekService = $deepSeekService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::OPTIONAL, 'User id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');

        $user = $this->userRepository->find($userId);
        if (empty($user)) {
            $io->error('User not found');
            return Command::FAILURE;
        }
        $messages = $user->getMessages();

        $messagesTexts = [];
        foreach ($messages as $message) {
            $messagesTexts[] = $message->getContent();
        }

        if(empty($messagesTexts) || count($messagesTexts) <10 ) {
            $io->error('Анализ провести невозможно. Мало сообщений');
            return Command::SUCCESS;
        }

        // После анализа от DeepSeek
        $analysis = $this->deepSeekService->analyzeUserPersonality($messagesTexts, $user->getUserContext());

        if (!empty($analysis['interests'])) {
            $user->addInterest($analysis['interests']);
        }
        if (!empty($analysis['personality_traits'])) {
            $user->setPersonalityTraits($analysis['personality_traits']);
        }
        $user->setLastAnalysisAt(new \DateTimeImmutable());

        $this->entityManager->flush();


        $io->success('Анализ завершен.');
        print_r($analysis);
        return Command::SUCCESS;
    }
}
