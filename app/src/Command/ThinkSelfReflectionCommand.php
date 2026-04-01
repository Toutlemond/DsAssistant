<?php

namespace App\Command;

use App\Repository\JobLoopRepository;
use App\Service\SelfIdentityService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'think:self-reflection',
    description: 'Add a short description for your command',
)]
class ThinkSelfReflectionCommand extends AbstractLoopCommand
{
    private LoggerInterface $testLogger;
    private SelfIdentityService $selfIdentityService;

    public function __construct(
        LoggerInterface        $testLogger,
        JobLoopRepository      $jobLoopRepository,
        CacheItemPoolInterface $cache,
        SelfIdentityService    $selfIdentityService
    )
    {
        parent::__construct($testLogger, $jobLoopRepository, $cache);
        $this->testLogger = $testLogger;
        $this->selfIdentityService = $selfIdentityService;

    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->selfReflect();

        $io->success('SelfReflection Completed');

        return Command::SUCCESS;
    }

    protected function executeIteration(InputInterface $input, OutputInterface $output, int $iteration): void
    {
        $this->selfReflect();
    }

    private function selfReflect()
    {
        $this->selfIdentityService->ensureAgentHasName();
        $this->selfIdentityService->performSelfReflection();
    }
}
