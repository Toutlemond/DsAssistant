<?php

namespace App\Command;

use App\Service\JobLoopRunnerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'job:start-job-loops')]
class StartJobLoopsCommand extends Command
{
    public function __construct(
        private JobLoopRunnerService $jobRunner
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->jobRunner->startAllJobLoops();
        $output->writeln('All job loops started');
        return Command::SUCCESS;
    }
}
