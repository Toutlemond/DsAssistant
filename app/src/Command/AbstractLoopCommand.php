<?php

namespace App\Command;

use App\Repository\JobLoopRepository;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractLoopCommand extends Command
{
    protected LoggerInterface $jobloopLogger;
    protected bool $shouldStop = false;
    private ?int $cachedSleepTime = null;
    private CacheItemPoolInterface $cache;
    private JobLoopRepository $jobLoopRepository;

    public function __construct(
        LoggerInterface $jobloopLogger,
        JobLoopRepository $jobLoopRepository,
        CacheItemPoolInterface $cache)
    {
        parent::__construct();
        $this->jobloopLogger = $jobloopLogger;
        $this->jobLoopRepository = $jobLoopRepository;
        $this->cache = $cache;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Обработка сигналов для graceful shutdown
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);

        $output->writeln(sprintf('Starting loop command: %s', $this->getName()));
        $this->jobloopLogger->info('Loop command started', ['command' => $this->getName()]);

        $iteration = 0;

        while (!$this->shouldStop) {
            $iteration++;
            $startTime = microtime(true);

            try {
                $this->jobloopLogger->debug('Command iteration started', [
                    'command' => $this->getName(),
                    'iteration' => $iteration
                ]);

                // Выполняем основную логику команды
                $this->executeIteration($input, $output, $iteration);

                $executionTime = microtime(true) - $startTime;
                $this->jobloopLogger->debug('Command iteration completed', [
                    'command' => $this->getName(),
                    'iteration' => $iteration,
                    'execution_time' => round($executionTime, 3)
                ]);

            } catch (\Exception $e) {
                $this->jobloopLogger->error('Error in command iteration', [
                    'command' => $this->getName(),
                    'iteration' => $iteration,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Ждем перед следующей итерацией
            if (!$this->shouldStop) {
                $sleepTime = $this->getSleepTime();
                if ($sleepTime > 0) {
                    sleep($sleepTime);
                }
            }
        }

        $output->writeln(sprintf('Stopping loop command: %s', $this->getName()));
        $this->jobloopLogger->info('Loop command stopped', ['command' => $this->getName()]);

        return Command::SUCCESS;
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): false|int
    {
        $this->jobloopLogger->info('Received signal, stopping command', [
            'command' => $this->getName(),
            'signal' => $signal
        ]);
        $this->shouldStop = true;
        return false;
    }

    /**
     * Основная логика команды - реализуется в дочерних классах
     */
    abstract protected function executeIteration(InputInterface $input, OutputInterface $output, int $iteration): void;

    /**

    /**
     * Получаем время сна из кэша или БД
     */
    protected function getSleepTime(): int
    {
        // Используем кэшированное значение если есть
        if ($this->cachedSleepTime !== null) {
            return $this->cachedSleepTime;
        }

        $cacheKey = 'jobloop_sleep_' . md5($this->getName());
        $cacheItem = $this->cache->getItem($cacheKey);

        // Пробуем получить из кэша
        if ($cacheItem->isHit()) {
            $this->cachedSleepTime = $cacheItem->get();
            $this->jobloopLogger->debug('Sleep time loaded from cache', [
                'command' => $this->getName(),
                'sleep_time' => $this->cachedSleepTime
            ]);
            return $this->cachedSleepTime;
        }

        // Если нет в кэше - загружаем из БД
        $jobLoop = $this->jobLoopRepository->findLoopByCommand($this->getName());

        if (!$jobLoop) {
            $this->jobloopLogger->warning('JobLoop not found, using default sleep time', [
                'command' => $this->getName()
            ]);
            $this->cachedSleepTime = 60; // Значение по умолчанию
        } else {
            $this->cachedSleepTime = $jobLoop->getSleep();
            $this->jobloopLogger->debug('Sleep time loaded from database', [
                'command' => $this->getName(),
                'sleep_time' => $this->cachedSleepTime
            ]);
        }

        // Сохраняем в кэш на 5 минут
        $cacheItem->set($this->cachedSleepTime);
        $cacheItem->expiresAfter(300); // 5 минут
        $this->cache->save($cacheItem);

        return $this->cachedSleepTime;
    }
}
