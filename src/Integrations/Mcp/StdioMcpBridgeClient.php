<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use Ferdiunal\Larapanda\Integrations\Exceptions\McpProtocolException;
use Ferdiunal\Larapanda\Integrations\Exceptions\McpSessionException;
use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpBridgeClientInterface;
use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpSessionManagerInterface;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * JSON-RPC stdio MCP bridge over session-scoped Lightpanda `mcp` processes.
 */
final class StdioMcpBridgeClient implements McpBridgeClientInterface
{
    private const TRANSPORT_NEWLINE = 'newline';

    private const TRANSPORT_FRAMED = 'framed';

    /**
     * @param  McpSessionManagerInterface  $sessions  Session lifecycle manager.
     * @param  float  $requestTimeoutSeconds  Max wait time for one JSON-RPC response.
     */
    public function __construct(
        private readonly McpSessionManagerInterface $sessions,
        private readonly float $requestTimeoutSeconds = 15.0,
    ) {
        if ($this->requestTimeoutSeconds <= 0.0) {
            throw new McpSessionException('MCP request timeout must be greater than zero.');
        }
    }

    /**
     * Perform MCP initialize handshake if needed, then invoke a `tools/call` request.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $toolName, array $arguments = [], ?string $sessionId = null): array
    {
        $session = $this->sessions->acquire($sessionId);
        $session->touch();
        $this->ensureInitialized($session);
        $transport = $this->transportForSession($session);
        $resolvedToolName = $this->resolveToolName($session, $toolName);

        $result = $this->sendRequestWithTransport(
            session: $session,
            method: 'tools/call',
            params: [
                'name' => $resolvedToolName,
                'arguments' => $arguments === [] ? (object) [] : $arguments,
            ],
            transport: $transport,
        );

        if (! is_array($result)) {
            throw new McpProtocolException('MCP tools/call returned a non-object result payload.');
        }

        /** @var array<string, mixed> $normalizedResult */
        $normalizedResult = [];

        foreach ($result as $key => $value) {
            if (is_string($key)) {
                $normalizedResult[$key] = $value;
            }
        }

        return $normalizedResult;
    }

    /**
     * Close one active session.
     */
    public function closeSession(string $sessionId): void
    {
        $this->sessions->release($sessionId);
    }

    /**
     * Close all active sessions.
     */
    public function closeAllSessions(): void
    {
        $this->sessions->releaseAll();
    }

    /**
     * Ensure MCP initialize + initialized handshake is completed once per session.
     */
    private function ensureInitialized(McpSession $session): void
    {
        if ($session->initialized()) {
            return;
        }

        $transport = $session->transport();

        if ($transport === null) {
            $transport = $this->negotiateTransportAndInitialize($session);
        } else {
            $this->sendRequestWithTransport(
                session: $session,
                method: 'initialize',
                params: $this->initializeParams(),
                transport: $transport,
            );
        }

        $this->sendNotification(
            session: $session,
            method: 'notifications/initialized',
            params: (object) [],
            transport: $transport,
        );

        $this->cacheAvailableTools($session, $transport);
        $session->markInitialized();
    }

    /**
     * Attempt newline-first initialization and fallback to framed JSON-RPC.
     *
     * @throws McpSessionException When both transport attempts fail.
     */
    private function negotiateTransportAndInitialize(McpSession $session): string
    {
        try {
            $this->sendRequestWithTransport(
                session: $session,
                method: 'initialize',
                params: $this->initializeParams(),
                transport: self::TRANSPORT_NEWLINE,
            );

            $session->setTransport(self::TRANSPORT_NEWLINE);

            return self::TRANSPORT_NEWLINE;
        } catch (Throwable $newlineException) {
            $this->resetParseState($session);

            try {
                $this->sendRequestWithTransport(
                    session: $session,
                    method: 'initialize',
                    params: $this->initializeParams(),
                    transport: self::TRANSPORT_FRAMED,
                );

                $session->setTransport(self::TRANSPORT_FRAMED);

                return self::TRANSPORT_FRAMED;
            } catch (Throwable $framedException) {
                $newlineMessage = trim($newlineException->getMessage());
                $framedMessage = trim($framedException->getMessage());

                throw new McpSessionException(
                    "MCP transport auto-detect failed. newline=[{$newlineMessage}] framed=[{$framedMessage}]",
                    previous: $framedException,
                );
            }
        }
    }

    /**
     * Send an MCP JSON-RPC notification without waiting for a response.
     *
     * @param  array<string, mixed>|object  $params
     */
    private function sendNotification(McpSession $session, string $method, array|object $params, string $transport): void
    {
        $this->writeMessage($session, [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
        ], $transport);
    }

    /**
     * Send request and wait for matching JSON-RPC response ID.
     *
     * @param  array<string, mixed>  $params
     */
    private function sendRequestWithTransport(McpSession $session, string $method, array $params, string $transport): mixed
    {
        $id = $session->nextRequestId();

        $this->writeMessage($session, [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ], $transport);

        $started = microtime(true);

        while ((microtime(true) - $started) <= $this->requestTimeoutSeconds) {
            $messages = $this->drainMessages($session, $transport);

            foreach ($messages as $message) {
                if (! is_array($message)) {
                    continue;
                }

                $responseId = $message['id'] ?? null;
                if (! is_int($responseId) && ! is_string($responseId)) {
                    continue;
                }

                if ((string) $responseId !== (string) $id) {
                    continue;
                }

                if (isset($message['error'])) {
                    $errorPayload = $message['error'];
                    $encodedError = $this->safeEncode($errorPayload);
                    throw new McpProtocolException("MCP request [{$method}] failed: {$encodedError}");
                }

                if (! array_key_exists('result', $message)) {
                    throw new McpProtocolException("MCP response for [{$method}] does not contain a result field.");
                }

                return $message['result'];
            }

            if (! $session->handle()->isRunning()) {
                $stderr = trim($session->handle()->readStderr());
                $detail = $stderr !== '' ? " stderr: {$stderr}" : '';
                throw new McpSessionException("MCP process terminated while waiting for [{$method}] response.{$detail}");
            }

            usleep(10_000);
        }

        throw new McpSessionException("Timed out waiting for MCP response to [{$method}] after {$this->requestTimeoutSeconds} seconds.");
    }

    /**
     * Write JSON-RPC payload to process stdin with negotiated transport framing.
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeMessage(McpSession $session, array $payload, string $transport): void
    {
        $json = $this->safeEncode($payload);
        $message = $transport === self::TRANSPORT_FRAMED
            ? 'Content-Length: '.strlen($json)."\r\n\r\n".$json
            : $json."\n";

        $session->handle()->writeStdin($message);
    }

    /**
     * Drain newly available stdout bytes and decode transport-specific JSON-RPC messages.
     *
     * @return list<mixed>
     */
    private function drainMessages(McpSession $session, string $transport): array
    {
        $stdout = $session->handle()->readStdout();
        $cursor = $session->stdoutCursor();
        $newChunk = substr($stdout, $cursor);

        if ($newChunk !== '') {
            $session->setStdoutCursor(strlen($stdout));
            $session->setParseBuffer($session->parseBuffer().$newChunk);
        }

        $messages = [];
        $buffer = $session->parseBuffer();

        $messages = $transport === self::TRANSPORT_FRAMED
            ? $this->parseFramedMessages($buffer)
            : $this->parseNewlineMessages($buffer);

        $session->setParseBuffer($buffer);

        return $messages;
    }

    /**
     * Parse Content-Length framed messages from a mutable buffer.
     *
     * @return list<mixed>
     */
    private function parseFramedMessages(string &$buffer): array
    {
        $messages = [];

        while (true) {
            $headerEnd = strpos($buffer, "\r\n\r\n");
            if ($headerEnd === false) {
                break;
            }

            $headerBlock = substr($buffer, 0, $headerEnd);
            $headers = preg_split('/\r\n/', $headerBlock) ?: [];
            $contentLength = null;

            foreach ($headers as $headerLine) {
                if (preg_match('/^Content-Length:\s*(\d+)$/i', trim($headerLine), $matches) === 1) {
                    $contentLength = (int) $matches[1];
                    break;
                }
            }

            if ($contentLength === null || $contentLength < 0) {
                throw new McpProtocolException('MCP frame is missing a valid Content-Length header.');
            }

            $payloadStart = $headerEnd + 4;
            if (strlen($buffer) < ($payloadStart + $contentLength)) {
                break;
            }

            $payload = substr($buffer, $payloadStart, $contentLength);
            $buffer = substr($buffer, $payloadStart + $contentLength);

            try {
                $messages[] = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new McpProtocolException('MCP frame payload is not valid JSON.', previous: $exception);
            }
        }

        return $messages;
    }

    /**
     * Parse newline-delimited JSON-RPC messages from a mutable buffer.
     *
     * @return list<mixed>
     */
    private function parseNewlineMessages(string &$buffer): array
    {
        $messages = [];

        while (true) {
            $lineBreak = strpos($buffer, "\n");
            if ($lineBreak === false) {
                break;
            }

            $line = trim(substr($buffer, 0, $lineBreak));
            $buffer = substr($buffer, $lineBreak + 1);

            if ($line === '' || ! str_starts_with($line, '{')) {
                continue;
            }

            try {
                $messages[] = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new McpProtocolException('MCP newline payload is not valid JSON.', previous: $exception);
            }
        }

        return $messages;
    }

    /**
     * Cache native tool definitions from `tools/list` payload for alias-aware invocation.
     */
    private function cacheAvailableTools(McpSession $session, string $transport): void
    {
        try {
            $result = $this->sendRequestWithTransport(
                session: $session,
                method: 'tools/list',
                params: [],
                transport: $transport,
            );
        } catch (Throwable) {
            $session->setAvailableTools([]);

            return;
        }

        if (! is_array($result)) {
            $session->setAvailableTools([]);

            return;
        }

        $tools = $result['tools'] ?? null;
        if (! is_array($tools)) {
            $session->setAvailableTools([]);

            return;
        }

        $available = [];

        foreach ($tools as $tool) {
            if (! is_array($tool)) {
                continue;
            }

            $name = $tool['name'] ?? null;
            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            /** @var array<string, mixed> $tool */
            $available[$name] = $tool;
        }

        $session->setAvailableTools($available);
    }

    /**
     * Resolve requested tool name against native capability cache and aliases.
     */
    private function resolveToolName(McpSession $session, string $toolName): string
    {
        $availableTools = $session->availableTools();
        if ($availableTools === []) {
            return $toolName;
        }

        if (isset($availableTools[$toolName])) {
            return $toolName;
        }

        foreach ($this->aliasesForTool($toolName) as $alias) {
            if (isset($availableTools[$alias])) {
                return $alias;
            }
        }

        return $toolName;
    }

    /**
     * Return supported alias alternatives for canonical tool names.
     *
     * @return list<string>
     */
    private function aliasesForTool(string $toolName): array
    {
        return match ($toolName) {
            'goto' => ['navigate'],
            'navigate' => ['goto'],
            'evaluate' => ['eval'],
            'eval' => ['evaluate'],
            default => [],
        };
    }

    /**
     * Return active transport for an initialized session.
     */
    private function transportForSession(McpSession $session): string
    {
        $transport = $session->transport();

        if ($transport === null) {
            throw new McpSessionException('MCP transport is not negotiated for the current session.');
        }

        return $transport;
    }

    /**
     * Drop buffered parse state before switching decoding strategy.
     */
    private function resetParseState(McpSession $session): void
    {
        $stdout = $session->handle()->readStdout();
        $session->setStdoutCursor(strlen($stdout));
        $session->setParseBuffer('');
    }

    /**
     * Return initialize params payload shared across transport modes.
     *
     * @return array<string, mixed>
     */
    private function initializeParams(): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities' => (object) [],
            'clientInfo' => [
                'name' => 'larapanda-bridge',
                'version' => '1.0.0',
            ],
        ];
    }

    private function safeEncode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to JSON-encode MCP payload.', previous: $exception);
        }
    }
}
