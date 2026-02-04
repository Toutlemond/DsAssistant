<?php

namespace App\Command;

use App\Entity\JobLoop;
use App\Repository\JobLoopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
#[AsCommand(
    name: 'job:job-loop-worker',
    description: 'Universal worker for job loops',
)]
class JobLoopWorkerCommand extends Command
{
    protected static $defaultName = 'job:job-loop-worker';
    protected static $defaultDescription = 'Universal worker for job loops';

    public function __construct(
        private JobLoopRepository $jobLoopRepository,
        private LoggerInterface $jobloopLogger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting universal job loop worker...');
$i = 0;
        while (true) {
            $i++;
            echo 'JobLoopWorkerCommandIteration: ' . $i . PHP_EOL;
            try {
                $this->processJobLoops($output);
                sleep(2); // Проверяем каждую секунду

            } catch (\Exception $e) {
                $this->jobloopLogger->error('Error in job loop worker', [
                    'error' => $e->getMessage()
                ]);
                sleep(5);
            }
        }

        return Command::SUCCESS;
    }

    private function processJobLoops(OutputInterface $output): void
    {
        // Очищаем кеш EntityManager перед каждым циклом
        $this->jobLoopRepository->getEntityManager()->clear();

        $allLoops = $this->jobLoopRepository->findAll();
        print_r($allLoops);


        /** @var JobLoop $jobLoop */
        foreach ($allLoops as $jobLoop) {

            echo $jobLoop->getCommand() . "\n";
            $currentStatus = $this->getCurrentJobStatus($jobLoop);

            print_r($currentStatus);
            echo PHP_EOL;
            if ($jobLoop->isActive() && !$currentStatus['isRunning']) {
                echo 'Должен работать, но не работает'.PHP_EOL;
                // Должен работать, но не работает
                $this->ensureJobLoopRunning($jobLoop, $output);
            } elseif (!$jobLoop->isActive() && $currentStatus['isRunning']) {
                echo 'Не должен работать, но работает'.PHP_EOL;
                // Не должен работать, но работает
                $this->ensureJobLoopStopped3($jobLoop, $output);
            } elseif ($jobLoop->isActive() && $currentStatus['isRunning']) {
                echo 'Должен работать и работает - логируем'.PHP_EOL;
                // Должен работать и работает - логируем
                $this->jobloopLogger->debug('Job loop is running correctly', [
                    'command' => $jobLoop->getCommand(),
                    'pid' => $currentStatus['pid']
                ]);
            }
            echo 'Остальные случаи - ничего не делаем'.PHP_EOL;
            // Остальные случаи - ничего не делаем
        }
    }

    private function getCurrentJobStatus(JobLoop $jobLoop): array
    {
        $processInfo = $this->getProcessInfo($jobLoop);

        return [
            'isRunning' => $processInfo ? $processInfo['isRunning'] : false,
            'pid' => $processInfo ? $processInfo['pid'] : null
        ];
    }

    private function getProcessInfo(JobLoop $jobLoop): ?array
    {
        $pidFile = sprintf('var/job_%d.pid', $jobLoop->getId());

        if (!file_exists($pidFile)) {
            return null;
        }

        $pid = file_get_contents($pidFile);
        echo 'pid: ' . $pid . PHP_EOL;
        if (!$pid) {
            unlink($pidFile);
            return null;
        }

        $isRunning = posix_kill($pid, 0);
        echo 'isRunning: ' . $isRunning . PHP_EOL;
        if (!$isRunning) {
            unlink($pidFile);
            return null;
        }

        return [
            'pid' => $pid,
            'pidFile' => $pidFile,
            'isRunning' => true
        ];
    }

    private function ensureJobLoopStopped(JobLoop $jobLoop, OutputInterface $output): void
    {
        $processInfo = $this->getProcessInfo($jobLoop);

        if ($processInfo && $processInfo['isRunning']) {
            // Процесс работает - останавливаем
            posix_kill($processInfo['pid'], SIGTERM);
            unlink($processInfo['pidFile']);

            $this->jobloopLogger->info('Stopped inactive job loop', [
                'command' => $jobLoop->getCommand(),
                'pid' => $processInfo['pid']
            ]);

            $output->writeln(sprintf('Stopped inactive job: %s (PID: %d)',
                $jobLoop->getCommand(), $processInfo['pid']));
        }
    }
    private function ensureJobLoopStopped2(JobLoop $jobLoop, OutputInterface $output): void
    {
        $processInfo = $this->getProcessInfo($jobLoop);

        if ($processInfo && $processInfo['isRunning']) {
            try {
                // Пробуем убить через Process
                $process = Process::fromShellCommandline(sprintf('kill %d', $processInfo['pid']));
                $process->run();

                if ($process->isSuccessful()) {
                    $output->writeln('Process killed successfully via kill command');
                } else {
                    $output->writeln('Kill command failed: ' . $process->getErrorOutput());
                }
            } catch (\Exception $e) {
                $output->writeln('Exception killing process: ' . $e->getMessage());
            }

            // Всегда удаляем PID файл
            if (file_exists($processInfo['pidFile'])) {
                unlink($processInfo['pidFile']);
            }
        }
    }


    private function ensureJobLoopStopped3(JobLoop $jobLoop, OutputInterface $output): void
    {
        $processInfo = $this->getProcessInfo($jobLoop);

        if ($processInfo && $processInfo['isRunning']) {
            $pid = $processInfo['pid'];

            $output->writeln("Stopping process PID: $pid");

            // 1. Пробуем мягкое завершение
            posix_kill($pid, SIGTERM);
            sleep(2);

            // 2. Проверяем статус
            if (posix_kill($pid, 0)) {
                $output->writeln("Process $pid still running, sending SIGKILL");
                posix_kill($pid, SIGKILL);
                sleep(1);
            }

            // 3. Важно: "убираем" зомби через waitpid
            $this->reapZombieProcess($pid);

            // 4. Удаляем PID файл
            if (file_exists($processInfo['pidFile'])) {
                unlink($processInfo['pidFile']);
            }

            $output->writeln("Process $pid stopped and reaped");
        }
    }

    private function reapZombieProcess($pid): void
    {
        // Используем pcntl_waitpid для уборки зомби
        if (function_exists('pcntl_waitpid')) {
            $status = null;
            pcntl_waitpid($pid, $status, WNOHANG);
        }

        // Альтернатива через system
        exec("ps -o state= -p $pid", $output);
        if (!empty($output) && trim($output[0]) === 'Z') {
            exec("kill -9 $pid"); // SIGKILL для зомби
        }
    }
    private function ensureJobLoopRunning(JobLoop $jobLoop, OutputInterface $output): void
    {
        $processInfo = $this->getProcessInfo($jobLoop);

        if ($processInfo) {
            if ($processInfo['isRunning']) {
                return; // Процесс работает
            }

            // PID файл есть, но процесс умер
            $this->jobloopLogger->warning('Job loop process died, restarting', [
                'command' => $jobLoop->getCommand(),
                'pid' => $processInfo['pid']
            ]);
        }

        // Запускаем процесс
        $this->startJobProcess($jobLoop, $output);
    }

    private function startJobProcess(JobLoop $jobLoop, OutputInterface $output): void
    {
        $command = ['php', 'bin/console', $jobLoop->getCommand()];

        $process = new Process($command);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        // Запускаем в фоне
        $process->start();

        // Сохраняем PID
        file_put_contents(
            sprintf('var/job_%d.pid', $jobLoop->getId()),
            $process->getPid()
        );

        $output->writeln(sprintf(
            'Started job: %s (PID: %d)',
            $jobLoop->getCommand(),
            $process->getPid()
        ));

        $this->jobloopLogger->info('Job loop process started due to active status', [
            'command' => $jobLoop->getCommand(),
            'pid' => $process->getPid(),
            'job_id' => $jobLoop->getId()
        ]);
    }
}
