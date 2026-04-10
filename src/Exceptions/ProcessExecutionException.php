<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Exceptions;

use Ferdiunal\Larapanda\Runtime\ProcessResult;
use Throwable;

/**
 * Exception wrapper for command execution failures with optional raw process context.
 */
final class ProcessExecutionException extends LarapandaException
{
    /**
     * @param  string  $message  Operator-facing error description.
     * @param  ProcessResult|null  $result  Optional process result payload.
     * @param  Throwable|null  $previous  Previous throwable in the causal chain.
     */
    public function __construct(
        string $message,
        private readonly ?ProcessResult $result = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create an exception instance from a completed process result.
     */
    public static function fromResult(ProcessResult $result): self
    {
        $stderr = trim($result->stderr());
        $details = $stderr !== '' ? " stderr: {$stderr}" : '';

        return new self(
            sprintf(
                'Lightpanda command failed with exit code %d.%s',
                $result->exitCode(),
                $details
            ),
            $result,
        );
    }

    /**
     * Create an exception instance from a startup failure.
     *
     * @param  list<string>  $command
     * @param  Throwable  $previous  Start failure cause.
     */
    public static function fromStartFailure(array $command, Throwable $previous): self
    {
        return new self(
            sprintf(
                'Failed to start Lightpanda process: %s. Command: %s',
                $previous->getMessage(),
                implode(' ', $command),
            ),
            null,
            $previous,
        );
    }

    /**
     * Return the associated process result when available.
     */
    public function result(): ?ProcessResult
    {
        return $this->result;
    }
}
