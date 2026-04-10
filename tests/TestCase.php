<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Tests;

use Ferdiunal\Larapanda\LarapandaServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case configuring package providers and factory name resolution.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Bootstrap test-specific factory naming behavior.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('larapanda.defaults.runtime', 'auto');
        config()->set('larapanda.defaults.binary_path', null);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Ferdiunal\\Larapanda\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * Register package service providers for Testbench.
     *
     * @param  mixed  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LarapandaServiceProvider::class,
        ];
    }

    /**
     * Configure environment defaults used by package tests.
     *
     * @param  mixed  $app
     */
    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
