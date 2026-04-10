<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime;

/**
 * Abstraction for running short-lived and long-lived process commands.
 */
interface ProcessRunner
{
    /**
     * Execute a command and wait for completion.
     *
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     */
    public function run(array $command, ?float $timeoutSeconds = null, array $environment = [], ?string $workingDirectory = null): ProcessResult;

    /**
     * Start a command and return a running process handle.
     *
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     */
    public function start(array $command, ?float $timeoutSeconds = null, array $environment = [], ?string $workingDirectory = null): RunningInstanceHandle;
}
