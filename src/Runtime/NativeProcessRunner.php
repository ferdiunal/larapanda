<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime;

use RuntimeException;

/**
 * Native process runner backed by `proc_open` and argv-safe execution.
 */
final class NativeProcessRunner implements ProcessRunner
{
    /**
     * Execute and wait for a process in one call.
     *
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     */
    public function run(array $command, ?float $timeoutSeconds = null, array $environment = [], ?string $workingDirectory = null): ProcessResult
    {
        return $this->start($command, $timeoutSeconds, $environment, $workingDirectory)->wait($timeoutSeconds);
    }

    /**
     * Start a process and return a running handle for lifecycle operations.
     *
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     *
     * @throws RuntimeException When process startup or pipe bootstrap fails.
     */
    public function start(array $command, ?float $timeoutSeconds = null, array $environment = [], ?string $workingDirectory = null): RunningInstanceHandle
    {
        unset($timeoutSeconds);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $command,
            $descriptors,
            $pipes,
            $workingDirectory,
            $environment === [] ? null : $environment,
            ['bypass_shell' => true],
        );

        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start process.');
        }

        if (! isset($pipes[0], $pipes[1], $pipes[2])) {
            throw new RuntimeException('Process pipes are not available.');
        }

        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        /** @var array{0: resource, 1: resource, 2: resource} $stdioPipes */
        $stdioPipes = [0 => $pipes[0], 1 => $pipes[1], 2 => $pipes[2]];

        return RunningInstanceHandle::fromProcess(
            process: $process,
            pipes: $stdioPipes,
            command: $command,
            redactedCommand: CommandRedactor::redact($command),
            startedAt: microtime(true),
        );
    }
}
