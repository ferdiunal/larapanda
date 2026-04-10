<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Enums\RuntimeMode;
use Ferdiunal\Larapanda\Exceptions\InvalidInstanceConfigurationException;
use Ferdiunal\Larapanda\LarapandaManager;
use Ferdiunal\Larapanda\Runtime\RuntimeResolver;
use Ferdiunal\Larapanda\Tests\Support\Fakes\FakeProcessRunner;

/**
 * Validate configuration hydration and instance profile resolution semantics.
 */
/**
 * Ensure defaults are merged into each named instance profile.
 */
it('merges default config into named instances', function (): void {
    $manager = new LarapandaManager(
        config: [
            'default_instance' => 'crawler',
            'defaults' => [
                'runtime' => 'docker',
                'docker' => [
                    'image' => 'lightpanda/browser:nightly',
                    'command' => 'docker',
                ],
            ],
            'instances' => [
                'default' => [],
                'crawler' => [
                    'runtime' => 'cli',
                    'binary_path' => '/opt/lightpanda/lightpanda',
                ],
            ],
        ],
        processRunner: new FakeProcessRunner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
    );

    $crawler = $manager->profile('crawler');
    $default = $manager->profile('default');

    expect($crawler->runtimeMode)->toBe(RuntimeMode::Cli)
        ->and($crawler->dockerImage)->toBe('lightpanda/browser:nightly')
        ->and($default->runtimeMode)->toBe(RuntimeMode::Docker);
});

/**
 * Ensure invalid default instance references fail fast.
 */
it('throws when configured default instance does not exist', function (): void {
    expect(fn (): LarapandaManager => new LarapandaManager([
        'default_instance' => 'unknown',
        'instances' => [
            'default' => [],
        ],
    ]))->toThrow(InvalidInstanceConfigurationException::class);
});

/**
 * Ensure unknown instance lookups surface typed configuration errors.
 */
it('throws for unknown instance requests', function (): void {
    $manager = new LarapandaManager([
        'instances' => [
            'default' => [],
        ],
    ]);

    expect(fn (): mixed => $manager->instance('missing'))
        ->toThrow(InvalidInstanceConfigurationException::class);
});
