<?php

namespace App\Service;

use App\Entity\JobLoop;
use App\Repository\JobLoopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class JobLoopRunnerService
{
    public function __construct(
        private JobLoopRepository $jobLoopRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $jobloopLogger
    ) {}

    public function startAllJobLoops(): void
    {
        $activeLoops = $this->jobLoopRepository->findActiveLoops();

        foreach ($activeLoops as $jobLoop) {
            $this->startJobLoopProcess($jobLoop);
        }
    }

    private function startJobLoopProcess(JobLoop $jobLoop): void
    {
        $command = ['php', 'bin/console', $jobLoop->getCommand()];

        $process = new Process($command);
        $process->setTimeout(null); // Без таймаута
        $process->setIdleTimeout(null);

        // Запускаем в фоне
        $process->start();

        $this->jobloopLogger->info('Started job loop process', [
            'command' => $jobLoop->getCommand(),
            'pid' => $process->getPid()
        ]);

        // Можно сохранить PID для управления процессами
        $this->trackProcess($jobLoop, $process->getPid());
    }

    private function trackProcess(JobLoop $jobLoop, int $pid): void
    {
        // Сохраняем информацию о процессе (можно в БД или файл)
        file_put_contents(
            sprintf('var/job_%d.pid', $jobLoop->getId()),
            $pid
        );
    }

    public function stopAllJobLoops(): void
    {
        $activeLoops = $this->jobLoopRepository->findActiveLoops();

        foreach ($activeLoops as $jobLoop) {
            $this->stopJobLoopProcess($jobLoop);
        }
    }

    private function stopJobLoopProcess(JobLoop $jobLoop): void
    {
        $pidFile = sprintf('var/job_%d.pid', $jobLoop->getId());

        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            if ($pid && posix_kill($pid, SIGTERM)) {
                $this->jobloopLogger->info('Stopped job loop process', [
                    'command' => $jobLoop->getCommand(),
                    'pid' => $pid
                ]);
            }
            unlink($pidFile);
        }
    }
}
