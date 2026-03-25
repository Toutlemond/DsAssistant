<?php

namespace App\Command;

use App\Service\InternalThinkingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(
    name: 'think:internal-thinking',
    description: 'Обработает один фокус ',
)]
class InternalThinkingCommand extends Command
{
    protected static $defaultName = 'think:internal-thinking';
    protected static $defaultDescription = 'Process one focus (internal thinking)';

    public function __construct(
        private InternalThinkingService $thinkingService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting internal thinking...');

        $thought = $this->thinkingService->thinkOnce();

        if ($thought) {
            $output->writeln('Thought generated: ' . substr($thought->getContent(), 0, 100) . '...');
            $this->logger->info('Internal thinking produced thought', [
                'thought_id' => $thought->getId()
            ]);
        } else {
            $output->writeln('No focus to process.');
        }

        return Command::SUCCESS;
    }
}
