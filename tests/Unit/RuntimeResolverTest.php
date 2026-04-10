<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Enums\RuntimeMode;
use Ferdiunal\Larapanda\Exceptions\InvalidInstanceConfigurationException;
use Ferdiunal\Larapanda\Exceptions\RuntimeUnavailableException;
use Ferdiunal\Larapanda\Runtime\RuntimeResolver;

/**
 * Validate runtime resolution behavior for explicit and auto runtime modes.
 */
/**
 * Ensure explicit CLI mode resolves when binary is executable.
 */
it('resolves cli runtime when cli is explicit and binary is executable', function (): void {
    $profile = InstanceProfile::fromArray('cli', [
        'runtime' => 'cli',
        'binary_path' => '/opt/lightpanda/lightpanda',
    ]);

    $resolver = new RuntimeResolver(
        isCommandAvailable: static fn (string $command): bool => $command === 'docker',
        isBinaryExecutable: static fn (string $binary): bool => $binary === '/opt/lightpanda/lightpanda',
    );

    expect($resolver->resolve($profile))->toBe(RuntimeMode::Cli);
});

/**
 * Ensure auto mode falls back to Docker when CLI binary is not configured.
 */
it('falls back to docker runtime in auto mode when binary is not configured', function (): void {
    $profile = InstanceProfile::fromArray('default', [
        'runtime' => 'auto',
        'docker' => [
            'command' => 'docker',
        ],
    ]);

    $resolver = new RuntimeResolver(
        isCommandAvailable: static fn (string $command): bool => $command === 'docker',
        isBinaryExecutable: static fn (string $binary): bool => $binary === '/opt/lightpanda/lightpanda',
    );

    expect($resolver->resolve($profile))->toBe(RuntimeMode::Docker);
});

/**
 * Ensure invalid auto-mode binary paths raise configuration exceptions.
 */
it('throws when auto runtime has an invalid binary path', function (): void {
    $profile = InstanceProfile::fromArray('broken', [
        'runtime' => 'auto',
        'binary_path' => '/missing/lightpanda',
    ]);

    $resolver = new RuntimeResolver(
        isCommandAvailable: static fn (): bool => true,
        isBinaryExecutable: static fn (): bool => false,
    );

    expect(fn (): RuntimeMode => $resolver->resolve($profile))
        ->toThrow(InvalidInstanceConfigurationException::class);
});

/**
 * Ensure explicit Docker mode fails when Docker command is unavailable.
 */
it('throws when docker runtime is selected but docker is unavailable', function (): void {
    $profile = InstanceProfile::fromArray('docker', [
        'runtime' => 'docker',
        'docker' => [
            'command' => 'docker',
        ],
    ]);

    $resolver = new RuntimeResolver(
        isCommandAvailable: static fn (): bool => false,
        isBinaryExecutable: static fn (): bool => false,
    );

    expect(fn (): RuntimeMode => $resolver->resolve($profile))
        ->toThrow(RuntimeUnavailableException::class);
});
