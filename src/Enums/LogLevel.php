<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Enums;

/**
 * Severity levels accepted by Lightpanda logging options.
 */
enum LogLevel: string
{
    /** Verbose diagnostic logging. */
    case Debug = 'debug';

    /** Informational operational logging. */
    case Info = 'info';

    /** Warning-level logging. */
    case Warn = 'warn';

    /** Error-level logging. */
    case Error = 'error';

    /** Fatal error logging. */
    case Fatal = 'fatal';
}
