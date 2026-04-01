<?php

namespace App\Command;

use App\Repository\JobLoopRepository;
use App\Service\InternalThinkingService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'think:internal-thinking',
    description: 'Обработает один фокус ',
)]
class InternalThinkingCommand extends AbstractLoopCommand
{
    protected static $defaultName = 'think:internal-thinking';
    protected static $defaultDescription = 'Process one focus (internal thinking)';
    private LoggerInterface $testLogger;
    private InternalThinkingService $thinkingService;

    public function __construct(
        LoggerInterface         $jobloopLogger,
        LoggerInterface         $testLogger,
        JobLoopRepository       $jobLoopRepository,
        CacheItemPoolInterface  $cache,
        InternalThinkingService $thinkingService
    )
    {
        parent::__construct($jobloopLogger, $jobLoopRepository, $cache);
        $this->testLogger = $testLogger;
        $this->thinkingService = $thinkingService;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }


    protected function executeIteration(InputInterface $input, OutputInterface $output, int $iteration): void
    {
        $this->work($input, $output);
    }

    private function work(InputInterface $input, OutputInterface $output, bool $log = false): void
    {
        if ($log) {
            $output->writeln('Starting internal thinking...');
        }
        $timer= microtime(true);
        $thought = $this->thinkingService->thinkOnce();
        $delta = microtime(true) - $timer;

        if ($thought) {
            if ($log) {
                $output->writeln('Timer: ' .$delta . ' seconds...');
                $output->writeln('Thought generated: ' . substr($thought->getContent(), 0, 100) . '...');
                $output->writeln('Prompt: ' . substr($thought->getPrompt(), 0, 100) . '...');
                $this->testLogger->info('Internal thinking produced thought', [
                    'thought_id' => $thought->getId()
                ]);
            }
        } else {
            if ($log) {
                $output->writeln('No focus to process.');
            }
        }
    }
}
