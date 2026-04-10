<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime;

/**
 * Immutable process execution snapshot with raw I/O and command metadata.
 */
final readonly class ProcessResult
{
    /**
     * @param  list<string>  $command
     * @param  list<string>  $redactedCommand
     */
    public function __construct(
        private array $command,
        private array $redactedCommand,
        private int $exitCode,
        private string $stdout,
        private string $stderr,
        private float $durationSeconds,
        private bool $timedOut = false,
    ) {}

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
     * Return raw process exit code.
     */
    public function exitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * Return collected standard output bytes.
     */
    public function stdout(): string
    {
        return $this->stdout;
    }

    /**
     * Return collected standard error bytes.
     */
    public function stderr(): string
    {
        return $this->stderr;
    }

    /**
     * Return process duration in seconds.
     */
    public function durationSeconds(): float
    {
        return $this->durationSeconds;
    }

    /**
     * Check whether process completion was caused by timeout.
     */
    public function timedOut(): bool
    {
        return $this->timedOut;
    }

    /**
     * Check whether process completed successfully without timeout.
     */
    public function isSuccessful(): bool
    {
        return ! $this->timedOut && $this->exitCode === 0;
    }
}
