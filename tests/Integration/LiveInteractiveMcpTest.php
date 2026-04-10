<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Integrations\Mcp\InMemoryMcpSessionManager;
use Ferdiunal\Larapanda\Integrations\Mcp\StdioMcpBridgeClient;
use Ferdiunal\Larapanda\Tests\Support\Live\LightpandaLiveTestSupport;

/**
 * Validate interactive MCP tool flow against a deterministic local fixture page.
 */
it('executes wait fill click scroll flow on a local fixture page', function (): void {
    $binaryPath = LightpandaLiveTestSupport::requireLiveBinaryOrSkip($this);
    $fixture = LightpandaLiveTestSupport::startFixtureServer($this, <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Larapanda Interactive Fixture</title>
  <style>
    body { font-family: sans-serif; min-height: 2400px; }
    #result { margin-top: 12px; color: #0b7a39; }
  </style>
</head>
<body>
  <h1>interactive-fixture</h1>
  <label for="name">Name</label>
  <input id="name" />
  <button id="submit-btn" type="button">Submit</button>
  <div id="result"></div>
  <script>
    document.getElementById('submit-btn').addEventListener('click', function () {
      const input = document.getElementById('name');
      const result = document.getElementById('result');
      result.textContent = 'Hello ' + (input.value || 'anonymous');
      result.classList.add('ready');
    });
  </script>
</body>
</html>
HTML);

    $manager = LightpandaLiveTestSupport::managerForBinary($binaryPath);
    $sessionManager = new InMemoryMcpSessionManager(
        manager: $manager,
        instance: 'default',
        sessionTtlSeconds: 300,
        maxSessions: 8,
        defaultSessionId: 'live-interactive-session',
    );

    $bridge = new StdioMcpBridgeClient(
        sessions: $sessionManager,
        requestTimeoutSeconds: 20.0,
    );

    try {
        $sessionId = 'live-interactive-session';
        $bridge->callTool('goto', ['url' => $fixture['url']], $sessionId);

        $nameWaitResult = $bridge->callTool('waitForSelector', ['selector' => '#name', 'timeout' => 5000], $sessionId);
        $nameNodeId = larapandaExtractBackendNodeId($nameWaitResult);

        expect($nameNodeId)->toBeInt();
        $bridge->callTool('fill', ['backendNodeId' => $nameNodeId, 'text' => 'Ferdi'], $sessionId);

        $buttonWaitResult = $bridge->callTool('waitForSelector', ['selector' => '#submit-btn', 'timeout' => 5000], $sessionId);
        $buttonNodeId = larapandaExtractBackendNodeId($buttonWaitResult);

        expect($buttonNodeId)->toBeInt();
        $bridge->callTool('click', ['backendNodeId' => $buttonNodeId], $sessionId);
        $bridge->callTool('waitForSelector', ['selector' => '#result.ready', 'timeout' => 5000], $sessionId);
        $bridge->callTool('scroll', ['y' => 300], $sessionId);

        $markdownResult = $bridge->callTool('markdown', [], $sessionId);
        expect((string) json_encode($markdownResult))->toContain('Hello Ferdi');
    } finally {
        $sessionManager->releaseAll();
        LightpandaLiveTestSupport::shutdownProcess(
            process: $fixture['process'],
            pipes: $fixture['pipes'],
            directory: $fixture['directory'],
        );
    }
})->group('live');

/**
 * Extract the first backend node identifier from a nested MCP result payload.
 *
 * @param  array<string, mixed>  $payload
 */
function larapandaExtractBackendNodeId(array $payload): ?int
{
    if (isset($payload['backendNodeId']) && is_int($payload['backendNodeId'])) {
        return $payload['backendNodeId'];
    }

    foreach ($payload as $value) {
        if (is_array($value)) {
            $candidate = larapandaExtractBackendNodeId($value);

            if (is_int($candidate)) {
                return $candidate;
            }
        }
    }

    return null;
}
