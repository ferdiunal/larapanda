<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;
use Ferdiunal\Larapanda\Integrations\Exceptions\McpSessionException;
use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpSessionManagerInterface;
use Ferdiunal\Larapanda\Options\McpOptions;
use InvalidArgumentException;

/**
 * In-memory MCP session manager that maps session IDs to live Lightpanda `mcp` processes.
 */
final class InMemoryMcpSessionManager implements McpSessionManagerInterface
{
    /** @var array<string, McpSession> */
    private array $sessions = [];

    /**
     * @param  LarapandaManagerInterface  $manager  Larapanda manager used to resolve the MCP instance profile.
     * @param  string  $instance  Instance profile key used for MCP process startup.
     * @param  int  $sessionTtlSeconds  Inactivity TTL in seconds before session shutdown.
     * @param  int  $maxSessions  Maximum allowed concurrent sessions.
     * @param  string  $defaultSessionId  Session key used when none is provided.
     * @param  McpOptions|null  $options  Method-scoped options applied to each started MCP process.
     */
    public function __construct(
        private readonly LarapandaManagerInterface $manager,
        private readonly string $instance,
        private readonly int $sessionTtlSeconds,
        private readonly int $maxSessions,
        private readonly string $defaultSessionId,
        private readonly ?McpOptions $options = null,
    ) {
        if ($this->sessionTtlSeconds < 1) {
            throw new InvalidArgumentException('MCP session TTL must be at least 1 second.');
        }

        if ($this->maxSessions < 1) {
            throw new InvalidArgumentException('MCP max sessions must be at least 1.');
        }
    }

    /**
     * Resolve or start a session, applying TTL cleanup before allocation.
     */
    public function acquire(?string $sessionId = null): McpSession
    {
        $this->cleanupExpiredSessions();

        $id = $this->normalizeSessionId($sessionId);

        $session = $this->sessions[$id] ?? null;
        if ($session instanceof McpSession) {
            if (! $session->handle()->isRunning()) {
                unset($this->sessions[$id]);
            } else {
                $session->touch();

                return $session;
            }
        }

        if (count($this->sessions) >= $this->maxSessions) {
            throw new McpSessionException("MCP session capacity reached [{$this->maxSessions}].");
        }

        $request = $this->manager->instance($this->instance)->mcpRequest();

        if ($this->options instanceof McpOptions) {
            $request = $request->withOptions($this->options);
        }

        $handle = $request->run();
        $session = new McpSession($id, $handle);
        $this->sessions[$id] = $session;

        return $session;
    }

    /**
     * Release one session and terminate its process.
     */
    public function release(string $sessionId): void
    {
        $id = $this->normalizeSessionId($sessionId);
        $session = $this->sessions[$id] ?? null;

        if (! $session instanceof McpSession) {
            return;
        }

        $session->handle()->stop();
        $session->handle()->wait(2.0);

        unset($this->sessions[$id]);
    }

    /**
     * Release all active sessions.
     */
    public function releaseAll(): void
    {
        foreach (array_keys($this->sessions) as $sessionId) {
            $this->release($sessionId);
        }
    }

    /**
     * Release expired sessions based on inactivity TTL.
     */
    public function cleanupExpiredSessions(): void
    {
        $now = microtime(true);

        foreach ($this->sessions as $sessionId => $session) {
            $expired = ($now - $session->lastUsedAt()) > $this->sessionTtlSeconds;
            $stopped = ! $session->handle()->isRunning();

            if (! $expired && ! $stopped) {
                continue;
            }

            $this->release($sessionId);
        }
    }

    /**
     * Normalize provided session key or return configured default.
     */
    private function normalizeSessionId(?string $sessionId): string
    {
        $normalized = trim($sessionId ?? '');

        if ($normalized !== '') {
            return $normalized;
        }

        return $this->defaultSessionId;
    }
}
