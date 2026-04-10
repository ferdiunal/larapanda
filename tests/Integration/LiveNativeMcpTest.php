<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Tests\Support\Live\LightpandaLiveTestSupport;
use Ferdiunal\Larapanda\Tests\Support\Live\LightpandaNativeMcpClient;

/**
 * Validate native Lightpanda MCP server behavior over direct stdio JSON-RPC.
 */
it('executes native mcp initialize list and goto/markdown flow', function (): void {
    $binaryPath = LightpandaLiveTestSupport::requireLiveBinaryOrSkip($this);
    $nativeClient = LightpandaNativeMcpClient::fromBinary($binaryPath);

    try {
        $nativeClient->initialize();
        $toolsResult = $nativeClient->listTools();
        $tools = $toolsResult['tools'] ?? null;

        expect($tools)->toBeArray();

        $toolNames = [];
        foreach ($tools as $tool) {
            if (is_array($tool) && isset($tool['name']) && is_string($tool['name'])) {
                $toolNames[] = $tool['name'];
            }
        }

        expect($toolNames)->toContain('goto')
            ->and($toolNames)->toContain('markdown')
            ->and($toolNames)->toContain('click')
            ->and($toolNames)->toContain('fill')
            ->and($toolNames)->toContain('scroll')
            ->and($toolNames)->toContain('waitForSelector');

        try {
            $gotoResult = $nativeClient->callTool('goto', ['url' => 'https://example.com']);
            $markdownResult = $nativeClient->callTool('markdown');
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'CouldntResolveHost')) {
                $this->markTestSkipped('Live DNS resolution is unavailable in this environment.');
            }

            throw $exception;
        }

        $encodedMarkdown = (string) json_encode($markdownResult);
        if (str_contains($encodedMarkdown, 'CouldntResolveHost')) {
            $this->markTestSkipped('Live DNS resolution is unavailable in this environment.');
        }

        expect($gotoResult)->toBeArray()
            ->and($markdownResult)->toBeArray()
            ->and($encodedMarkdown)->toContain('Example Domain');
    } finally {
        $nativeClient->close();
    }
})->group('live');
