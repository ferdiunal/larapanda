<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Exceptions;

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Throwable;

/**
 * Raised when typed fetch output accessors are used with an incompatible dump format.
 */
final class UnexpectedFetchOutputFormatException extends LarapandaException
{
    /**
     * @param  FetchDumpFormat  $expected  Accessor-required dump format.
     * @param  FetchDumpFormat|null  $actual  Actual dump format used during fetch execution.
     */
    public static function forExpected(FetchDumpFormat $expected, ?FetchDumpFormat $actual): self
    {
        $actualLabel = $actual instanceof FetchDumpFormat ? $actual->value : 'none';

        return new self(
            sprintf(
                'Fetch output format mismatch. Expected [%s], actual [%s].',
                $expected->value,
                $actualLabel
            )
        );
    }

    /**
     * Create exception for invalid semantic tree payloads.
     *
     * @param  string  $reason  Parse failure reason.
     * @param  Throwable|null  $previous  Previous throwable from JSON decode.
     */
    public static function invalidSemanticTree(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Unable to decode semantic tree output: %s.', $reason),
            0,
            $previous,
        );
    }
}
