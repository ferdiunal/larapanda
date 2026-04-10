<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda;

use Ferdiunal\Larapanda\Contracts\LarapandaClientInterface;
use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;

/**
 * Thin convenience wrapper around the instance manager.
 */
final readonly class Larapanda
{
    /**
     * @param  LarapandaManagerInterface  $manager  Resolved manager implementation from the service container.
     */
    public function __construct(
        private LarapandaManagerInterface $manager,
    ) {}

    /**
     * Resolve a named Lightpanda client instance.
     *
     * @param  string  $name  Logical instance name configured under `larapanda.instances`.
     * @return LarapandaClientInterface Runtime-ready client bound to the selected instance profile.
     */
    public function instance(string $name = 'default'): LarapandaClientInterface
    {
        return $this->manager->instance($name);
    }
}
