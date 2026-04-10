<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Commands;

use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan command that verifies runtime resolution for a named Larapanda instance.
 */
final class LarapandaCommand extends Command
{
    protected $signature = 'larapanda:diagnose {instance=default : Instance profile name}';

    protected $description = 'Diagnose configured Larapanda instance runtime resolution';

    /**
     * Execute command diagnostics and print resolved runtime settings.
     */
    public function handle(): int
    {
        $instance = (string) $this->argument('instance');

        /** @var LarapandaManagerInterface $manager */
        $manager = $this->laravel->make(LarapandaManagerInterface::class);

        try {
            $profile = $manager->instance($instance)->profile();
        } catch (Throwable $exception) {
            $this->error("Instance [{$instance}] cannot be resolved: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->components->info("Instance [{$profile->name}] configured.");
        $this->line("Runtime mode: {$profile->runtimeMode->value}");
        $this->line('Binary path: '.($profile->binaryPath ?? '(docker fallback)'));
        $this->line("Docker image: {$profile->dockerImage}");

        return self::SUCCESS;
    }
}
