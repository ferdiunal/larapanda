<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Exceptions\UnexpectedFetchOutputFormatException;
use Ferdiunal\Larapanda\Runtime\FetchResult;
use Ferdiunal\Larapanda\Runtime\ProcessResult;

/**
 * Validate strict typed fetch output accessors.
 */

/**
 * Ensure HTML accessor returns payload only when dump format is HTML.
 */
it('returns html output via typed accessor', function (): void {
    $result = new FetchResult(
        process: baseProcessResult(),
        output: '<html><body>ok</body></html>',
        dumpFormat: FetchDumpFormat::Html,
    );

    expect($result->dumpFormat())->toBe(FetchDumpFormat::Html)
        ->and($result->asHtml())->toContain('<body>ok</body>');
});

/**
 * Ensure Markdown accessor enforces strict dump format matching.
 */
it('throws on typed accessor format mismatch', function (): void {
    $result = new FetchResult(
        process: baseProcessResult(),
        output: '# Heading',
        dumpFormat: FetchDumpFormat::Markdown,
    );

    expect(fn (): string => $result->asHtml())
        ->toThrow(UnexpectedFetchOutputFormatException::class);
});

/**
 * Ensure semantic tree accessor decodes JSON object payloads.
 */
it('decodes semantic tree output to associative array', function (): void {
    $result = new FetchResult(
        process: baseProcessResult(),
        output: '{"root":{"role":"document"},"version":1}',
        dumpFormat: FetchDumpFormat::SemanticTree,
    );

    expect($result->asSemanticTree())
        ->toBe([
            'root' => ['role' => 'document'],
            'version' => 1,
        ]);
});

/**
 * Ensure semantic tree accessor fails fast on invalid JSON payloads.
 */
it('throws when semantic tree payload is invalid json', function (): void {
    $result = new FetchResult(
        process: baseProcessResult(),
        output: '{"root":',
        dumpFormat: FetchDumpFormat::SemanticTree,
    );

    expect(fn (): array => $result->asSemanticTree())
        ->toThrow(UnexpectedFetchOutputFormatException::class);
});

/**
 * Ensure semantic tree text accessor returns payload only for semantic-tree-text format.
 */
it('returns semantic tree text via typed accessor', function (): void {
    $result = new FetchResult(
        process: baseProcessResult(),
        output: "document\n  heading",
        dumpFormat: FetchDumpFormat::SemanticTreeText,
    );

    expect($result->asSemanticTreeText())->toContain('document');
});

/**
 * Create a neutral successful process snapshot for fetch result tests.
 */
function baseProcessResult(): ProcessResult
{
    return new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://example.com'],
        redactedCommand: ['lightpanda', 'fetch', 'https://example.com'],
        exitCode: 0,
        stdout: '',
        stderr: '',
        durationSeconds: 0.01,
    );
}
