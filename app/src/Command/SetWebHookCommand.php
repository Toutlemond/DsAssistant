<?php

namespace App\Command;

use App\Service\TelegramBotService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'app:set-web-hook',
    description: 'Add a short description for your command',
)]
class SetWebHookCommand extends Command
{
    protected static $defaultName = 'app:set-web-hook';
    protected static $defaultDescription = 'Set Telegram webhook URL';
    private TelegramBotService $telegramBotService;


    public function __construct(TelegramBotService $telegramBotService)
    {
        parent::__construct();
        $this->telegramBotService = $telegramBotService;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $result = $this->telegramBotService->setWebhook();
            $io->success("Webhook setup result:\n");
            $io->success($result);
        } catch (Exception $e) {
            $io->error( "Error: " . $e->getMessage() . "\n");
        }
        return Command::SUCCESS;
    }
}
