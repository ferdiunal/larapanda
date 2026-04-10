<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Integrations\Mcp\InMemoryMcpSessionManager;
use Ferdiunal\Larapanda\Integrations\Mcp\StdioMcpBridgeClient;
use Ferdiunal\Larapanda\Tests\Support\Live\LightpandaLiveTestSupport;

/**
 * Validate Larapanda MCP bridge behavior against a real Lightpanda binary process.
 */
it('executes bridge mcp goto/markdown flow and caches tool inventory', function (): void {
    $binaryPath = LightpandaLiveTestSupport::requireLiveBinaryOrSkip($this);
    $manager = LightpandaLiveTestSupport::managerForBinary($binaryPath);

    $sessionManager = new InMemoryMcpSessionManager(
        manager: $manager,
        instance: 'default',
        sessionTtlSeconds: 300,
        maxSessions: 8,
        defaultSessionId: 'live-bridge-session',
    );

    $bridge = new StdioMcpBridgeClient(
        sessions: $sessionManager,
        requestTimeoutSeconds: 20.0,
    );

    try {
        try {
            $gotoResult = $bridge->callTool('goto', ['url' => 'https://example.com'], 'live-bridge-session');
            $markdownResult = $bridge->callTool('markdown', [], 'live-bridge-session');
        } catch (Throwable $exception) {
            if (str_contains($exception->getMessage(), 'CouldntResolveHost')) {
                $this->markTestSkipped('Live DNS resolution is unavailable in this environment.');
            }

            throw $exception;
        }

        $encodedMarkdown = (string) json_encode($markdownResult);
        if (str_contains($encodedMarkdown, 'CouldntResolveHost')) {
            $this->markTestSkipped('Live DNS resolution is unavailable in this environment.');
        }

        $session = $sessionManager->acquire('live-bridge-session');

        expect($gotoResult)->toBeArray()
            ->and($markdownResult)->toBeArray()
            ->and($encodedMarkdown)->toContain('Example Domain')
            ->and($session->availableTools())->toHaveKey('goto')
            ->and($session->availableTools())->toHaveKey('markdown');
    } finally {
        $sessionManager->releaseAll();
    }
})->group('live');
