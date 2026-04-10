<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Enums;

/**
 * Output encodings supported by Lightpanda logging flags.
 */
enum LogFormat: string
{
    /** Human-readable pretty log format. */
    case Pretty = 'pretty';

    /** Structured key/value logfmt format. */
    case Logfmt = 'logfmt';
}
