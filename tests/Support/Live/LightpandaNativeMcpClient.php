<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Tests\Support\Live;

use RuntimeException;

/**
 * Minimal newline-delimited JSON-RPC client for native `lightpanda mcp` live tests.
 */
final class LightpandaNativeMcpClient
{
    /** @var resource */
    private mixed $process;

    /** @var array{0: resource, 1: resource, 2: resource} */
    private array $pipes;

    private int $nextId = 1;

    /**
     * @param  resource  $process
     * @param  array{0: resource, 1: resource, 2: resource}  $pipes
     */
    private function __construct(mixed $process, array $pipes)
    {
        $this->process = $process;
        $this->pipes = $pipes;
    }

    /**
     * Start a native Lightpanda MCP process.
     */
    public static function fromBinary(string $binaryPath): self
    {
        $process = proc_open(
            [$binaryPath, 'mcp'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (! is_resource($process) || ! isset($pipes[0], $pipes[1], $pipes[2])) {
            throw new RuntimeException('Could not start native Lightpanda MCP process.');
        }

        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        /** @var array{0: resource, 1: resource, 2: resource} $stdioPipes */
        $stdioPipes = [0 => $pipes[0], 1 => $pipes[1], 2 => $pipes[2]];

        return new self($process, $stdioPipes);
    }

    /**
     * Execute the MCP initialize handshake.
     */
    public function initialize(): void
    {
        $this->request('initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities' => (object) [],
            'clientInfo' => [
                'name' => 'larapanda-live-tests',
                'version' => '1.0.0',
            ],
        ]);

        $this->notify('notifications/initialized', (object) []);
    }

    /**
     * List native MCP tools.
     *
     * @return array<string, mixed>
     */
    public function listTools(): array
    {
        $result = $this->request('tools/list', (object) []);

        if (! is_array($result)) {
            throw new RuntimeException('Native tools/list response is not an object.');
        }

        return $result;
    }

    /**
     * Call one native MCP tool.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $name, array $arguments = []): array
    {
        $result = $this->request('tools/call', [
            'name' => $name,
            'arguments' => $arguments === [] ? (object) [] : $arguments,
        ]);

        if (! is_array($result)) {
            throw new RuntimeException('Native tools/call response is not an object.');
        }

        return $result;
    }

    /**
     * Terminate process and close pipes.
     */
    public function close(): void
    {
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($this->process)) {
            proc_terminate($this->process, 15);
            usleep(100_000);
            proc_terminate($this->process, 9);
            proc_close($this->process);
        }
    }

    /**
     * Send one request and block until matching response arrives.
     *
     * @param  array<string, mixed>|object  $params
     */
    private function request(string $method, array|object $params): mixed
    {
        $id = $this->nextId++;
        $this->write([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ]);

        $startedAt = microtime(true);
        $buffer = '';

        while ((microtime(true) - $startedAt) < 10.0) {
            $chunk = stream_get_contents($this->pipes[1]);
            if (is_string($chunk) && $chunk !== '') {
                $buffer .= $chunk;
            }

            while (($lineBreak = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $lineBreak));
                $buffer = substr($buffer, $lineBreak + 1);

                if ($line === '') {
                    continue;
                }

                $message = json_decode($line, true);
                if (! is_array($message)) {
                    continue;
                }

                $responseId = $message['id'] ?? null;
                if ((string) $responseId !== (string) $id) {
                    continue;
                }

                if (isset($message['error'])) {
                    throw new RuntimeException('Native MCP request failed: '.json_encode($message['error']));
                }

                if (! array_key_exists('result', $message)) {
                    throw new RuntimeException("Native MCP response for [{$method}] does not include result.");
                }

                return $message['result'];
            }

            usleep(10_000);
        }

        $stderr = stream_get_contents($this->pipes[2]);
        $detail = is_string($stderr) ? trim($stderr) : '';
        $message = $detail !== '' ? $detail : 'timed out waiting for response';

        throw new RuntimeException("Native MCP request timed out for [{$method}]: {$message}");
    }

    /**
     * Send one notification payload.
     *
     * @param  array<string, mixed>|object  $params
     */
    private function notify(string $method, array|object $params): void
    {
        $this->write([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function write(array $payload): void
    {
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR)."\n";
        $written = fwrite($this->pipes[0], $encoded);

        if ($written === false) {
            throw new RuntimeException('Unable to write to native MCP stdin.');
        }

        fflush($this->pipes[0]);
    }
}
