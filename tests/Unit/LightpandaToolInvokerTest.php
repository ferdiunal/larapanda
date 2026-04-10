<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolCatalog;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolInputValidator;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolInvoker;
use Ferdiunal\Larapanda\Tests\Support\Fakes\FakeMcpBridgeClient;

/**
 * Verify normalized tool invocation behavior and session propagation.
 */
it('validates arguments and forwards canonical tool call to bridge', function (): void {
    $bridge = new FakeMcpBridgeClient(['result' => 'ok']);
    $invoker = new LightpandaToolInvoker(
        bridgeClient: $bridge,
        catalog: new LightpandaToolCatalog,
        validator: new LightpandaToolInputValidator,
    );

    $result = $invoker->invoke('markdown', [
        'url' => 'https://example.com',
        'session_id' => 'session-1',
        'ignored' => 'value',
    ]);

    expect($result)->toBe(['result' => 'ok'])
        ->and($bridge->calls)->toHaveCount(1)
        ->and($bridge->calls[0]['tool'])->toBe('markdown')
        ->and($bridge->calls[0]['sessionId'])->toBe('session-1')
        ->and($bridge->calls[0]['arguments'])->toBe([
            'url' => 'https://example.com',
        ]);
});

/**
 * Ensure required arguments are enforced before bridge execution.
 */
it('throws when required arguments are missing', function (): void {
    $invoker = new LightpandaToolInvoker(
        bridgeClient: new FakeMcpBridgeClient,
        catalog: new LightpandaToolCatalog,
        validator: new LightpandaToolInputValidator,
    );

    expect(fn (): array => $invoker->invoke('goto', []))
        ->toThrow(InvalidArgumentException::class);
});

/**
 * Ensure argument type mismatches are rejected deterministically.
 */
it('throws when argument type does not match schema', function (): void {
    $invoker = new LightpandaToolInvoker(
        bridgeClient: new FakeMcpBridgeClient,
        catalog: new LightpandaToolCatalog,
        validator: new LightpandaToolInputValidator,
    );

    expect(fn (): array => $invoker->invoke('waitForSelector', ['selector' => '#app', 'timeout' => '100']))
        ->toThrow(InvalidArgumentException::class);
});
