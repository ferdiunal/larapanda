<?php

declare(strict_types=1);

/**
 * Architectural safeguard against accidental debugging calls.
 */
/**
 * Ensure debugging helper functions are not used in package code.
 */
arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();
