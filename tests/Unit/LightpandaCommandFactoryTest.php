<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Enums\RuntimeMode;
use Ferdiunal\Larapanda\Enums\StripMode;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;
use Ferdiunal\Larapanda\Runtime\Command\LightpandaCommandFactory;
use Ferdiunal\Larapanda\Runtime\CommandRedactor;

/**
 * Validate argv-safe command generation and sensitive argument redaction.
 */
/**
 * Ensure fetch command builder maps typed options to expected CLI flags.
 */
it('builds fetch command with typed option flags', function (): void {
    $profile = InstanceProfile::fromArray('default', [
        'runtime' => 'cli',
        'binary_path' => '/opt/lightpanda/lightpanda',
    ]);

    $options = (new FetchOptions)
        ->withDump(FetchDumpFormat::Html)
        ->withStripMode(StripMode::Js)
        ->withWaitMs(1500)
        ->withObeyRobots()
        ->withProxyBearerToken('super-secret-token');

    $factory = new LightpandaCommandFactory;
    $command = $factory->buildFetchCommand(
        profile: $profile,
        runtime: RuntimeMode::Cli,
        options: $options,
        url: 'https://example.com/?q=$(cat /etc/passwd)',
    );

    expect($command[0])->toBe('/opt/lightpanda/lightpanda')
        ->and($command)->toContain('fetch')
        ->and($command)->toContain('--dump')
        ->and($command)->toContain('html')
        ->and($command)->toContain('--strip-mode')
        ->and($command)->toContain('js')
        ->and($command)->toContain('--proxy-bearer-token')
        ->and($command)->toContain('https://example.com/?q=$(cat /etc/passwd)');

    $redacted = CommandRedactor::redact($command);

    expect($redacted)->toContain('***')
        ->and($redacted)->not->toContain('super-secret-token');
});

/**
 * Ensure proxy options are mapped to explicit CLI flags.
 */
it('builds fetch command with explicit proxy flags', function (): void {
    $profile = InstanceProfile::fromArray('default', [
        'runtime' => 'cli',
        'binary_path' => '/opt/lightpanda/lightpanda',
    ]);

    $options = (new FetchOptions)
        ->withHttpProxy('http://127.0.0.1:3000')
        ->withProxyBearerToken('proxy-token');

    $factory = new LightpandaCommandFactory;
    $command = $factory->buildFetchCommand(
        profile: $profile,
        runtime: RuntimeMode::Cli,
        options: $options,
        url: 'https://example.com',
    );

    expect($command)->toContain('--http-proxy')
        ->and($command)->toContain('http://127.0.0.1:3000')
        ->and($command)->toContain('--proxy-bearer-token')
        ->and($command)->toContain('proxy-token');
});

/**
 * Ensure docker serve command includes published CDP port and runtime arguments.
 */
it('builds docker serve command with published cdp port', function (): void {
    $profile = InstanceProfile::fromArray('crawler', [
        'runtime' => 'docker',
        'docker' => [
            'command' => 'docker',
            'image' => 'lightpanda/browser:nightly',
            'container_name' => 'larapanda-lightpanda',
            'extra_args' => ['--init'],
        ],
    ]);

    $factory = new LightpandaCommandFactory;
    $command = $factory->buildServeCommand(
        profile: $profile,
        runtime: RuntimeMode::Docker,
        options: (new ServeOptions)->withHost('127.0.0.1')->withPort(9222),
    );

    expect($command[0])->toBe('docker')
        ->and($command[1])->toBe('run')
        ->and($command)->toContain('-p')
        ->and($command)->toContain('127.0.0.1:9222:9222')
        ->and($command)->toContain('lightpanda/browser:nightly')
        ->and($command)->toContain('serve');
});
