<?php

namespace App\Command;

use App\Service\InternalThinkingService;
use App\Service\VisionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(
    name: 'think:vision-test',
    description: 'Обработает зрение',
)]
class VisionCommand extends Command
{
    protected static $defaultName = 'think:vision-test';
    protected static $defaultDescription = 'Обработает зрение';

    public function __construct(
        private VisionService $visionService,
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
        $output->writeln('Starting vision processing..');

        $dir = __DIR__;
        $path = $dir.'/../../public/pict/vision/snapshot.jpeg';
        echo $path . PHP_EOL;
        $description = $this->visionService->analyzeImage($path);

        if ($description) {
            $output->writeln('vision description generated: ' . $description. '...');
//            $this->logger->info('Internal thinking produced thought', [
//                'thought_id' => $thought->getId()
//            ]);
        } else {
            $output->writeln('No description');
        }

        return Command::SUCCESS;
    }
}
