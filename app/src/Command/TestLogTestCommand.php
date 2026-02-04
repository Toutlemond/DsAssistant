<?php

namespace App\Command;

use App\Repository\JobLoopRepository;
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
    name: 'test:log-test-command',
    description: 'Add a short description for your command',
)]
class TestLogTestCommand extends AbstractLoopCommand
{

    private LoggerInterface $testLogger;

    public function __construct(
        LoggerInterface $jobloopLogger,
        LoggerInterface $testLogger,
        JobLoopRepository $jobLoopRepository,
        CacheItemPoolInterface $cache

    )
    {
        parent::__construct($jobloopLogger,$jobLoopRepository,$cache);
        $this->testLogger = $testLogger;
    }
    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function executeIteration(InputInterface $input, OutputInterface $output, int $iteration): void
    {
        $this->testLogger->info('Test log entry', [
            'iteration' => $iteration,
            'timestamp' => date('Y-m-d H:i:s'),
            'pid' => getmypid()
        ]);

        $output->writeln("Iteration #{$iteration} at: " . date('Y-m-d H:i:s'));
    }
}
