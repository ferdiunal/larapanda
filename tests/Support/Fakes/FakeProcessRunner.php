<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Tests\Support\Fakes;

use Ferdiunal\Larapanda\Runtime\CommandRedactor;
use Ferdiunal\Larapanda\Runtime\ProcessResult;
use Ferdiunal\Larapanda\Runtime\ProcessRunner;
use Ferdiunal\Larapanda\Runtime\RunningInstanceHandle;
use Throwable;

/**
 * In-memory process runner fake for deterministic command execution tests.
 */
final class FakeProcessRunner implements ProcessRunner
{
    /** @var list<list<string>> */
    public array $runCommands = [];

    /** @var list<list<string>> */
    public array $startCommands = [];

    /** @var list<ProcessResult|Throwable> */
    private array $runQueue = [];

    /** @var list<RunningInstanceHandle|Throwable> */
    private array $startQueue = [];

    /**
     * Queue a process result for the next `run` invocation.
     */
    public function queueRunResult(ProcessResult $result): void
    {
        $this->runQueue[] = $result;
    }

    /**
     * Queue an exception for the next `run` invocation.
     */
    public function queueRunException(Throwable $throwable): void
    {
        $this->runQueue[] = $throwable;
    }

    /**
     * Queue a running handle for the next `start` invocation.
     */
    public function queueStartHandle(RunningInstanceHandle $handle): void
    {
        $this->startQueue[] = $handle;
    }

    /**
     * Queue an exception for the next `start` invocation.
     */
    public function queueStartException(Throwable $throwable): void
    {
        $this->startQueue[] = $throwable;
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     *
     * @throws Throwable When queued failure is present.
     */
    public function run(array $command, ?float $timeoutSeconds = null, array $environment = [], ?string $workingDirectory = null): ProcessResult
    {
        unset($timeoutSeconds, $environment, $workingDirectory);

        $this->runCommands[] = $command;
        $queued = array_shift($this->runQueue);

        if ($queued instanceof Throwable) {
            throw $queued;
        }

        if ($queued instanceof ProcessResult) {
            return $queued;
        }

        return new ProcessResult($command, CommandRedactor::redact($command), 0, 'ok', '', 0.01);
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     *
     * @throws Throwable When queued failure is present.
     */
    public function start(array $command, ?float $timeoutSeconds = null, array $environment = [], ?string $workingDirectory = null): RunningInstanceHandle
    {
        unset($timeoutSeconds, $environment, $workingDirectory);

        $this->startCommands[] = $command;
        $queued = array_shift($this->startQueue);

        if ($queued instanceof Throwable) {
            throw $queued;
        }

        if ($queued instanceof RunningInstanceHandle) {
            return $queued;
        }

        $result = new ProcessResult($command, CommandRedactor::redact($command), 0, '', '', 0.01);

        return RunningInstanceHandle::fromResult($result);
    }
}
