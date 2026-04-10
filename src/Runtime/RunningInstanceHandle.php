<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime;

use RuntimeException;

/**
 * Lifecycle handle for long-running Lightpanda processes.
 */
final class RunningInstanceHandle
{
    /** @var resource|null */
    private mixed $process = null;

    /** @var array{0: resource, 1: resource, 2: resource}|null */
    private ?array $pipes = null;

    /** @var list<string> */
    private array $command = [];

    /** @var list<string> */
    private array $redactedCommand = [];

    private ?int $pid = null;

    private float $startedAt = 0.0;

    private string $stdoutBuffer = '';

    private string $stderrBuffer = '';

    private ?ProcessResult $finalResult = null;

    private bool $stdinClosed = false;

    /**
     * Create a handle via static constructors only.
     */
    private function __construct() {}

    /**
     * Create a handle from a live process resource.
     *
     * @param  resource  $process
     * @param  array{0: resource, 1: resource, 2: resource}  $pipes
     * @param  list<string>  $command
     * @param  list<string>  $redactedCommand
     */
    public static function fromProcess(
        mixed $process,
        array $pipes,
        array $command,
        array $redactedCommand,
        float $startedAt,
    ): self {
        $self = new self;
        $self->process = $process;
        $self->pipes = $pipes;
        $self->command = $command;
        $self->redactedCommand = $redactedCommand;
        $self->startedAt = $startedAt;

        $status = proc_get_status($process);
        $self->pid = (int) $status['pid'];

        return $self;
    }

    /**
     * Create a completed handle from an already finalized process result.
     */
    public static function fromResult(ProcessResult $result): self
    {
        $self = new self;
        $self->command = $result->command();
        $self->redactedCommand = $result->redactedCommand();
        $self->finalResult = $result;

        return $self;
    }

    /**
     * @return list<string>
     */
    public function command(): array
    {
        return $this->command;
    }

    /**
     * @return list<string>
     */
    public function redactedCommand(): array
    {
        return $this->redactedCommand;
    }

    /**
     * Return process identifier when available.
     */
    public function pid(): ?int
    {
        return $this->pid;
    }

    /**
     * Check whether the underlying process is still running.
     */
    public function isRunning(): bool
    {
        if ($this->finalResult !== null) {
            return false;
        }

        if (! is_resource($this->process)) {
            return false;
        }

        $status = proc_get_status($this->processResource());

        return (bool) $status['running'];
    }

    /**
     * Read buffered stdout content from the process.
     */
    public function readStdout(): string
    {
        $this->collectOutput();

        return $this->stdoutBuffer;
    }

    /**
     * Read buffered stderr content from the process.
     */
    public function readStderr(): string
    {
        $this->collectOutput();

        return $this->stderrBuffer;
    }

    /**
     * Write bytes to process stdin.
     *
     * @param  string  $data  Raw data to write.
     *
     * @throws RuntimeException When stdin is closed or process is unavailable.
     */
    public function writeStdin(string $data): void
    {
        if ($this->finalResult !== null) {
            throw new RuntimeException('Cannot write to stdin after process completion.');
        }

        if ($this->pipes === null || ! is_resource($this->pipes[0])) {
            throw new RuntimeException('Process stdin is not available.');
        }

        if ($this->stdinClosed) {
            throw new RuntimeException('Process stdin pipe is already closed.');
        }

        $written = fwrite($this->pipes[0], $data);
        if ($written === false) {
            throw new RuntimeException('Unable to write to process stdin.');
        }

        fflush($this->pipes[0]);
    }

    /**
     * Close process stdin while keeping stdout/stderr open.
     */
    public function closeStdin(): void
    {
        $this->closeInput();
    }

    /**
     * Request process termination using the provided signal.
     */
    public function stop(int $signal = 15): void
    {
        if ($this->finalResult !== null || ! is_resource($this->process)) {
            return;
        }

        $this->closeInput();
        proc_terminate($this->processResource(), $signal);
    }

    /**
     * Wait until process exit or timeout and return a finalized process result.
     *
     * @throws RuntimeException When waiting is requested for a non-running process.
     */
    public function wait(?float $timeoutSeconds = null): ProcessResult
    {
        if ($this->finalResult !== null) {
            return $this->finalResult;
        }

        if (! is_resource($this->process)) {
            throw new RuntimeException('Cannot wait for a process that is not running.');
        }

        $start = $this->startedAt > 0 ? $this->startedAt : microtime(true);
        $timedOut = false;

        while (true) {
            $this->collectOutput();

            $status = proc_get_status($this->processResource());
            $running = (bool) $status['running'];

            if (! $running) {
                break;
            }

            if ($timeoutSeconds !== null && (microtime(true) - $start) > $timeoutSeconds) {
                $timedOut = true;
                $this->stop();
                usleep(100_000);
                $this->stop(9);
                break;
            }

            usleep(10_000);
        }

        $this->collectOutput();
        $exitCode = $this->closeProcess();
        $durationSeconds = max(0.0, microtime(true) - $start);

        $this->finalResult = new ProcessResult(
            command: $this->command,
            redactedCommand: $this->redactedCommand,
            exitCode: $exitCode,
            stdout: $this->stdoutBuffer,
            stderr: $this->stderrBuffer,
            durationSeconds: $durationSeconds,
            timedOut: $timedOut,
        );

        return $this->finalResult;
    }

    /**
     * Ensure running process resources are cleaned up when handle is destroyed.
     */
    public function __destruct()
    {
        if ($this->finalResult === null && is_resource($this->process)) {
            $this->closeInput();
            $this->stop();
            $this->closeProcess();
        }
    }

    /**
     * Drain currently available process output into internal buffers.
     */
    private function collectOutput(): void
    {
        if ($this->pipes === null) {
            return;
        }

        $stdoutChunk = stream_get_contents($this->pipes[1]);
        if (is_string($stdoutChunk) && $stdoutChunk !== '') {
            $this->stdoutBuffer .= $stdoutChunk;
        }

        $stderrChunk = stream_get_contents($this->pipes[2]);
        if (is_string($stderrChunk) && $stderrChunk !== '') {
            $this->stderrBuffer .= $stderrChunk;
        }
    }

    /**
     * Close process pipes and return exit code.
     */
    private function closeProcess(): int
    {
        $this->closeInput();

        if ($this->pipes !== null) {
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            $this->pipes = null;
        }

        if (! is_resource($this->process)) {
            return 0;
        }

        $exitCode = proc_close($this->process);
        $this->process = null;

        return $exitCode;
    }

    /**
     * Close process stdin pipe when available.
     */
    private function closeInput(): void
    {
        if ($this->pipes === null || $this->stdinClosed) {
            return;
        }

        if (is_resource($this->pipes[0])) {
            fclose($this->pipes[0]);
        }

        $this->stdinClosed = true;
    }

    /**
     * @return resource
     *
     * @throws RuntimeException When the process resource is no longer valid.
     */
    private function processResource(): mixed
    {
        if (! is_resource($this->process)) {
            throw new RuntimeException('Process handle is no longer available.');
        }

        return $this->process;
    }
}
