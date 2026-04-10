<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Enums;

/**
 * Post-processing strip strategies for fetch output.
 */
enum StripMode: string
{
    /** Strip JavaScript artifacts. */
    case Js = 'js';

    /** Strip UI-only fragments. */
    case Ui = 'ui';

    /** Strip CSS markup and style blocks. */
    case Css = 'css';

    /** Apply the full stripping profile. */
    case Full = 'full';
}
