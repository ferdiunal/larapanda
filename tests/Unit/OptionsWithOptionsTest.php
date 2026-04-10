<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Enums\LogLevel;
use Ferdiunal\Larapanda\Enums\StripMode;
use Ferdiunal\Larapanda\Enums\WaitUntil;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;

/**
 * Validate ergonomic `withOptions` mapping across all option objects.
 */

/**
 * Ensure fetch `withOptions` maps both common and fetch-specific fields without mutating base object.
 */
it('applies fetch withOptions fields immutably', function (): void {
    $base = new FetchOptions;
    $updated = $base->withOptions(
        dump: FetchDumpFormat::Markdown,
        stripModes: [StripMode::Css],
        withBase: true,
        withFrames: true,
        waitMs: 1200,
        waitUntil: WaitUntil::NetworkIdle,
        httpTimeout: 2500,
        retries: 2,
    );

    expect($base->dump())->toBeNull()
        ->and($base->waitMs())->toBe(5000)
        ->and($base->withBaseEnabled())->toBeFalse()
        ->and($updated->dump())->toBe(FetchDumpFormat::Markdown)
        ->and($updated->stripModes())->toBe([StripMode::Css])
        ->and($updated->withBaseEnabled())->toBeTrue()
        ->and($updated->withFramesEnabled())->toBeTrue()
        ->and($updated->waitMs())->toBe(1200)
        ->and($updated->waitUntil())->toBe(WaitUntil::NetworkIdle)
        ->and($updated->httpTimeout())->toBe(2500)
        ->and($updated->retries())->toBe(2);
});

/**
 * Ensure serve `withOptions` maps both common and serve-specific fields without mutating base object.
 */
it('applies serve withOptions fields immutably', function (): void {
    $base = new ServeOptions;
    $updated = $base->withOptions(
        host: '0.0.0.0',
        port: 9333,
        advertiseHost: 'public.local',
        timeoutSeconds: 30,
        cdpMaxConnections: 32,
        cdpMaxPendingConnections: 256,
        logLevel: LogLevel::Info,
    );

    expect($base->host())->toBe('127.0.0.1')
        ->and($base->port())->toBe(9222)
        ->and($updated->host())->toBe('0.0.0.0')
        ->and($updated->port())->toBe(9333)
        ->and($updated->advertiseHost())->toBe('public.local')
        ->and($updated->timeoutSeconds())->toBe(30)
        ->and($updated->cdpMaxConnections())->toBe(32)
        ->and($updated->cdpMaxPendingConnections())->toBe(256)
        ->and($updated->logLevel())->toBe(LogLevel::Info);
});

/**
 * Ensure MCP `withOptions` maps common fields and preserves immutable behavior.
 */
it('applies mcp withOptions fields immutably', function (): void {
    $base = new McpOptions;
    $updated = $base->withOptions(
        httpProxy: 'http://proxy.local:8080',
        userAgentSuffix: '  larapanda-sdk  ',
        logFilterScopes: ['http', ' http ', ''],
        retries: 3,
    );

    expect($base->httpProxy())->toBeNull()
        ->and($base->userAgentSuffix())->toBeNull()
        ->and($base->retries())->toBe(0)
        ->and($updated->httpProxy())->toBe('http://proxy.local:8080')
        ->and($updated->userAgentSuffix())->toBe('larapanda-sdk')
        ->and($updated->logFilterScopes())->toBe(['http'])
        ->and($updated->retries())->toBe(3);
});

/**
 * Ensure `withOptions` delegates validation to existing strict setters.
 */
it('delegates range validation through withOptions', function (): void {
    expect(fn (): FetchOptions => (new FetchOptions)->withOptions(waitMs: -1))
        ->toThrow(InvalidArgumentException::class);

    expect(fn (): ServeOptions => (new ServeOptions)->withOptions(port: 70000))
        ->toThrow(InvalidArgumentException::class);
});
