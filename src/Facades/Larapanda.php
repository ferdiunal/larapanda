<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Facades;

use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;
use Ferdiunal\Larapanda\LarapandaManager;
use Illuminate\Support\Facades\Facade;

/**
 * Facade accessor for the Larapanda manager service.
 *
 * @see LarapandaManager
 */
final class Larapanda extends Facade
{
    /**
     * Return the service container key for the facade root.
     */
    protected static function getFacadeAccessor(): string
    {
        return LarapandaManagerInterface::class;
    }
}
