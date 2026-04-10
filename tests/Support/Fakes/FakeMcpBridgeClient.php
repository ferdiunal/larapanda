<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Tests\Support\Fakes;

use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpBridgeClientInterface;

/**
 * Deterministic in-memory MCP bridge fake for invoker-level tests.
 */
final class FakeMcpBridgeClient implements McpBridgeClientInterface
{
    /** @var list<array{tool: string, arguments: array<string, mixed>, sessionId: string|null}> */
    public array $calls = [];

    /**
     * @param  array<string, mixed>  $nextResult
     */
    public function __construct(
        public array $nextResult = ['ok' => true],
    ) {}

    /**
     * Record call arguments and return preconfigured result.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $toolName, array $arguments = [], ?string $sessionId = null): array
    {
        $this->calls[] = [
            'tool' => $toolName,
            'arguments' => $arguments,
            'sessionId' => $sessionId,
        ];

        return $this->nextResult;
    }

    /**
     * No-op in fake bridge implementation.
     */
    public function closeSession(string $sessionId): void
    {
        unset($sessionId);
    }

    /**
     * No-op in fake bridge implementation.
     */
    public function closeAllSessions(): void {}
}
