<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Enums;

/**
 * Runtime execution modes supported by instance profiles.
 */
enum RuntimeMode: string
{
    /** Prefer CLI when configured, otherwise fallback to Docker. */
    case Auto = 'auto';

    /** Force local Lightpanda binary execution. */
    case Cli = 'cli';

    /** Force Docker container execution. */
    case Docker = 'docker';
}
