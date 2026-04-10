<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Contracts;

/**
 * Contract for resolving named Larapanda clients.
 */
interface LarapandaManagerInterface
{
    /**
     * Resolve a client instance by profile name.
     *
     * @param  string  $name  Configured instance key.
     * @return LarapandaClientInterface Runtime-ready client instance.
     */
    public function instance(string $name = 'default'): LarapandaClientInterface;
}
