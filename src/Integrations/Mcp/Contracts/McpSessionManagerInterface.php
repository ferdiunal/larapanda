<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp\Contracts;

use Ferdiunal\Larapanda\Integrations\Mcp\McpSession;

/**
 * Session lifecycle contract for MCP bridge process orchestration.
 */
interface McpSessionManagerInterface
{
    /**
     * Resolve or create an active session for MCP tool calls.
     *
     * @param  string|null  $sessionId  Optional caller-provided session key.
     */
    public function acquire(?string $sessionId = null): McpSession;

    /**
     * Release one session and terminate its process.
     *
     * @param  string  $sessionId  Session key.
     */
    public function release(string $sessionId): void;

    /**
     * Release all active sessions.
     */
    public function releaseAll(): void;

    /**
     * Purge and terminate sessions that exceeded inactivity TTL.
     */
    public function cleanupExpiredSessions(): void;
}
