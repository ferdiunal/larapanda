<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use Ferdiunal\Larapanda\Runtime\RunningInstanceHandle;

/**
 * Mutable MCP session state bound to one Lightpanda stdio process.
 */
final class McpSession
{
    private bool $initialized = false;

    private int $nextRequestId = 1;

    private int $stdoutCursor = 0;

    private string $parseBuffer = '';

    private ?string $transport = null;

    /** @var array<string, array<string, mixed>> */
    private array $availableTools = [];

    private float $lastUsedAt;

    public function __construct(
        private readonly string $id,
        private readonly RunningInstanceHandle $handle,
    ) {
        $this->lastUsedAt = microtime(true);
    }

    /**
     * Return caller-visible session identifier.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Return running MCP process handle.
     */
    public function handle(): RunningInstanceHandle
    {
        return $this->handle;
    }

    /**
     * Mark session as initialized after MCP handshake completes.
     */
    public function markInitialized(): void
    {
        $this->initialized = true;
    }

    /**
     * Check whether MCP initialize handshake already completed.
     */
    public function initialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Allocate and return the next JSON-RPC request identifier.
     */
    public function nextRequestId(): int
    {
        return $this->nextRequestId++;
    }

    /**
     * Return current stdout read cursor.
     */
    public function stdoutCursor(): int
    {
        return $this->stdoutCursor;
    }

    /**
     * Update stdout read cursor after consuming process output.
     */
    public function setStdoutCursor(int $stdoutCursor): void
    {
        $this->stdoutCursor = $stdoutCursor;
    }

    /**
     * Return currently buffered but not yet parsed JSON-RPC data.
     */
    public function parseBuffer(): string
    {
        return $this->parseBuffer;
    }

    /**
     * Replace buffered parse data after decoding pass.
     */
    public function setParseBuffer(string $parseBuffer): void
    {
        $this->parseBuffer = $parseBuffer;
    }

    /**
     * Return negotiated MCP transport mode (`newline` or `framed`) when available.
     */
    public function transport(): ?string
    {
        return $this->transport;
    }

    /**
     * Persist negotiated MCP transport mode for this session.
     */
    public function setTransport(?string $transport): void
    {
        $this->transport = $transport;
    }

    /**
     * Return the cached available MCP tool definitions keyed by tool name.
     *
     * @return array<string, array<string, mixed>>
     */
    public function availableTools(): array
    {
        return $this->availableTools;
    }

    /**
     * Replace the available tool cache from a `tools/list` result payload.
     *
     * @param  array<string, array<string, mixed>>  $availableTools
     */
    public function setAvailableTools(array $availableTools): void
    {
        $this->availableTools = $availableTools;
    }

    /**
     * Refresh inactivity timestamp for TTL tracking.
     */
    public function touch(): void
    {
        $this->lastUsedAt = microtime(true);
    }

    /**
     * Return last activity timestamp in seconds.
     */
    public function lastUsedAt(): float
    {
        return $this->lastUsedAt;
    }
}
