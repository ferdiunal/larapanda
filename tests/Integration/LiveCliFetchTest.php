<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Exceptions\ProcessExecutionException;
use Ferdiunal\Larapanda\Tests\Support\Live\LightpandaLiveTestSupport;

/**
 * Validate live CLI fetch flows against external URLs with strict typed accessors.
 */
it('fetches markdown and semantic outputs from a live URL', function (): void {
    $binaryPath = LightpandaLiveTestSupport::requireLiveBinaryOrSkip($this);
    $manager = LightpandaLiveTestSupport::managerForBinary($binaryPath);
    $client = $manager->instance('default');

    try {
        $markdownResult = $client->fetchRequest('https://example.com')
            ->withOptions(
                dump: FetchDumpFormat::Markdown,
                obeyRobots: true,
                waitMs: 2000,
            )
            ->run();

        $semanticTreeResult = $client->fetchRequest('https://example.com')
            ->withOptions(
                dump: FetchDumpFormat::SemanticTree,
                obeyRobots: true,
                waitMs: 2000,
            )
            ->run();

        $semanticTreeTextResult = $client->fetchRequest('https://example.com')
            ->withOptions(
                dump: FetchDumpFormat::SemanticTreeText,
                obeyRobots: true,
                waitMs: 2000,
            )
            ->run();
    } catch (ProcessExecutionException $exception) {
        if (str_contains($exception->getMessage(), 'CouldntResolveHost')) {
            $this->markTestSkipped('Live DNS resolution is unavailable in this environment.');
        }

        throw $exception;
    }

    expect($markdownResult->asMarkdown())->toContain('Example Domain')
        ->and($semanticTreeResult->asSemanticTree())->toBeArray()
        ->and($semanticTreeResult->asSemanticTree())->not->toBe([])
        ->and($semanticTreeTextResult->asSemanticTreeText())->toContain('Example Domain');
})->group('live');

/**
 * Validate request-level proxy options in live mode when a proxy endpoint is provided.
 */
it('supports proxy-driven live fetch when proxy environment is configured', function (): void {
    $proxy = getenv('LIGHTPANDA_HTTP_PROXY');
    if (! is_string($proxy) || trim($proxy) === '') {
        $this->markTestSkipped('Set LIGHTPANDA_HTTP_PROXY to run live proxy smoke test.');
    }

    $binaryPath = LightpandaLiveTestSupport::requireLiveBinaryOrSkip($this);
    $manager = LightpandaLiveTestSupport::managerForBinary($binaryPath);
    $client = $manager->instance('default');

    $token = getenv('LIGHTPANDA_PROXY_BEARER_TOKEN');
    $proxyToken = is_string($token) && trim($token) !== '' ? $token : null;

    try {
        $result = $client->fetchRequest('https://example.com')
            ->withOptions(
                dump: FetchDumpFormat::Markdown,
                obeyRobots: true,
                waitMs: 2000,
                httpProxy: $proxy,
                proxyBearerToken: $proxyToken,
            )
            ->run();
    } catch (ProcessExecutionException $exception) {
        if (str_contains($exception->getMessage(), 'CouldntResolveHost')) {
            $this->markTestSkipped('Live DNS resolution is unavailable in this environment.');
        }

        throw $exception;
    }

    expect($result->asMarkdown())->toContain('Example Domain');
})->group('live');
