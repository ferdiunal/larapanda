<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp\Contracts;

/**
 * Bridge contract for invoking Lightpanda MCP tools through a stdio JSON-RPC channel.
 */
interface McpBridgeClientInterface
{
    /**
     * Call an MCP tool on a session-scoped Lightpanda server process.
     *
     * @param  string  $toolName  MCP tool method name exposed by Lightpanda.
     * @param  array<string, mixed>  $arguments  Tool arguments payload.
     * @param  string|null  $sessionId  Optional caller-defined session key.
     * @return array<string, mixed> Normalized `tools/call` result payload.
     */
    public function callTool(string $toolName, array $arguments = [], ?string $sessionId = null): array;

    /**
     * Close a specific session and terminate its MCP process.
     */
    public function closeSession(string $sessionId): void;

    /**
     * Close all active sessions and terminate underlying MCP processes.
     */
    public function closeAllSessions(): void;
}
