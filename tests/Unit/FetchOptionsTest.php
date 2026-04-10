<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Enums\StripMode;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;

/**
 * Validate immutable option defaults and boundary checks.
 */
/**
 * Ensure documented fetch defaults match the runtime contract.
 */
it('uses documented defaults for fetch options', function (): void {
    $options = new FetchOptions;

    expect($options->waitMs())->toBe(5000)
        ->and($options->dump())->toBeNull()
        ->and($options->stripModes())->toBe([])
        ->and($options->withBaseEnabled())->toBeFalse()
        ->and($options->withFramesEnabled())->toBeFalse()
        ->and($options->httpTimeout())->toBe(10000)
        ->and($options->httpMaxConcurrent())->toBe(10)
        ->and($options->httpMaxHostOpen())->toBe(4);
});

/**
 * Ensure fluent option methods preserve immutability.
 */
it('keeps options immutable when using fluent methods', function (): void {
    $base = new FetchOptions;
    $custom = $base
        ->withWaitMs(1200)
        ->withDump(FetchDumpFormat::Markdown)
        ->withStripMode(StripMode::Css);

    expect($base->waitMs())->toBe(5000)
        ->and($base->dump())->toBeNull()
        ->and($custom->waitMs())->toBe(1200)
        ->and($custom->dump())->toBe(FetchDumpFormat::Markdown)
        ->and($custom->stripModes())->toBe([StripMode::Css]);
});

/**
 * Ensure numeric option setters enforce range validation.
 */
it('validates numeric option boundaries', function (): void {
    expect(fn (): FetchOptions => (new FetchOptions)->withWaitMs(-1))
        ->toThrow(InvalidArgumentException::class);

    expect(fn (): ServeOptions => (new ServeOptions)->withPort(70000))
        ->toThrow(InvalidArgumentException::class);
});
