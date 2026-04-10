<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Enums;

/**
 * Page readiness checkpoints used by `fetch --wait-until`.
 */
enum WaitUntil: string
{
    /** Wait until full page load completes. */
    case Load = 'load';

    /** Wait until DOM content is parsed. */
    case DomContentLoaded = 'domcontentloaded';

    /** Wait until network activity is considered idle. */
    case NetworkIdle = 'networkidle';

    /** Wait until Lightpanda internal completion condition is met. */
    case Done = 'done';
}
