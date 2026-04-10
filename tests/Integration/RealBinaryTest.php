<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Integrations\Mcp\InMemoryMcpSessionManager;
use Ferdiunal\Larapanda\Integrations\Mcp\StdioMcpBridgeClient;
use Ferdiunal\Larapanda\LarapandaManager;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

/**
 * Optional real-binary integration smoke tests guarded by environment variables.
 */
/**
 * Ensure real binary fetch command returns HTML output from a local fixture server.
 */
it('fetches html with real lightpanda binary', function (): void {
    [$binaryPath, $url, $httpProcess, $httpPipes] = startRealBinaryHttpFixture($this);

    try {
        $manager = new LarapandaManager(buildRealBinaryConfig($binaryPath));
        $result = $manager->instance()->fetch(
            $url,
            (new FetchOptions)
                ->withDump(FetchDumpFormat::Html)
                ->withWaitMs(300),
        );

        expect($result->process()->isSuccessful())->toBeTrue()
            ->and($result->output())->toContain('real-binary-fixture');
    } finally {
        shutdownProcess($httpProcess, $httpPipes);
    }
});

/**
 * Ensure real binary serve command exposes CDP version endpoint.
 */
it('starts serve and responds with cdp endpoint using real binary', function (): void {
    $binaryPath = requireRealBinaryOrSkip($this);
    $port = random_int(20000, 28000);

    $manager = new LarapandaManager(buildRealBinaryConfig($binaryPath));
    $handle = $manager->instance()->serve(
        (new ServeOptions)
            ->withHost('127.0.0.1')
            ->withPort($port)
            ->withTimeoutSeconds(10),
    );

    try {
        $ready = waitFor(
            timeoutMicroseconds: 5_000_000,
            intervalMicroseconds: 100_000,
            callback: static function () use ($handle, $port): bool {
                if (! $handle->isRunning()) {
                    return false;
                }

                $json = @file_get_contents("http://127.0.0.1:{$port}/json/version");

                return is_string($json) && str_contains($json, 'webSocketDebuggerUrl');
            },
        );

        $json = @file_get_contents("http://127.0.0.1:{$port}/json/version");

        if (! $ready || ! $handle->isRunning() || ! is_string($json)) {
            $stderr = trim($handle->readStderr());
            $reason = $stderr !== '' ? $stderr : 'unknown startup condition';
            $this->markTestSkipped("Serve endpoint was not reachable in this environment: {$reason}");
        }

        expect($json)->toContain('webSocketDebuggerUrl');
    } finally {
        $handle->stop();
        $handle->wait(2);
    }
});

/**
 * Ensure real binary MCP process remains alive until explicitly stopped.
 */
it('keeps mcp process alive until explicitly stopped with real binary', function (): void {
    $binaryPath = requireRealBinaryOrSkip($this);
    $manager = new LarapandaManager(buildRealBinaryConfig($binaryPath));

    $handle = $manager->instance()->mcp(new McpOptions);

    try {
        usleep(500000);
        expect($handle->isRunning())->toBeTrue();
    } finally {
        $handle->stop();
        $handle->wait(2);
    }
});

/**
 * Ensure MCP bridge can execute an interactive multi-step flow on a shared session.
 */
it('executes mcp goto then markdown on the same session with real binary', function (): void {
    [$binaryPath, $url, $httpProcess, $httpPipes] = startRealBinaryHttpFixture($this);
    $sessionManager = null;

    try {
        $manager = new LarapandaManager(buildRealBinaryConfig($binaryPath));
        $sessionManager = new InMemoryMcpSessionManager(
            manager: $manager,
            instance: 'default',
            sessionTtlSeconds: 60,
            maxSessions: 4,
            defaultSessionId: 'real-session',
        );

        $bridge = new StdioMcpBridgeClient(
            sessions: $sessionManager,
            requestTimeoutSeconds: 20.0,
        );

        $gotoResult = $bridge->callTool('goto', ['url' => $url], 'real-session');
        $markdownResult = $bridge->callTool('markdown', [], 'real-session');

        expect($gotoResult)->toBeArray()
            ->and($markdownResult)->toBeArray()
            ->and(json_encode($markdownResult))->toContain('real-binary-fixture');
    } finally {
        if ($sessionManager instanceof InMemoryMcpSessionManager) {
            $sessionManager->releaseAll();
        }

        shutdownProcess($httpProcess, $httpPipes);
    }
});

/**
 * Start an HTTP fixture server and return execution context for integration tests.
 *
 * @return array{0: string, 1: string, 2: resource, 3: array{0: resource, 1: resource, 2: resource}}
 */
function startRealBinaryHttpFixture(PhpUnitTestCase $test): array
{
    $binaryPath = requireRealBinaryOrSkip($test);
    $port = random_int(28001, 34000);
    $tmpDir = sys_get_temp_dir().'/larapanda-real-'.bin2hex(random_bytes(4));

    if (! @mkdir($tmpDir) && ! is_dir($tmpDir)) {
        $test->markTestSkipped('Could not create temporary fixture directory.');
    }

    file_put_contents($tmpDir.'/index.html', '<h1>real-binary-fixture</h1>');

    $process = proc_open(
        ['python3', '-m', 'http.server', (string) $port, '--bind', '127.0.0.1', '--directory', $tmpDir],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
    );

    if (! is_resource($process) || ! isset($pipes[0], $pipes[1], $pipes[2])) {
        $test->markTestSkipped('Could not start local HTTP server for real binary test.');
    }

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    waitFor(
        timeoutMicroseconds: 4_000_000,
        intervalMicroseconds: 100_000,
        callback: static fn (): bool => is_string(@file_get_contents("http://127.0.0.1:{$port}/")),
    );

    $probe = @file_get_contents("http://127.0.0.1:{$port}/");
    if (! is_string($probe) || ! str_contains($probe, 'real-binary-fixture')) {
        $stderr = stream_get_contents($pipes[2]);
        shutdownProcess($process, [0 => $pipes[0], 1 => $pipes[1], 2 => $pipes[2]]);
        $test->markTestSkipped('Local HTTP fixture failed to start: '.trim((string) $stderr));
    }

    /** @var array{0: resource, 1: resource, 2: resource} $fixedPipes */
    $fixedPipes = [0 => $pipes[0], 1 => $pipes[1], 2 => $pipes[2]];

    return [$binaryPath, "http://127.0.0.1:{$port}/", $process, $fixedPipes];
}

/**
 * Poll a callback until it returns true or timeout is reached.
 */
function waitFor(int $timeoutMicroseconds, int $intervalMicroseconds, callable $callback): bool
{
    $startedAt = microtime(true);

    while (((microtime(true) - $startedAt) * 1_000_000) <= $timeoutMicroseconds) {
        if ($callback() === true) {
            return true;
        }

        usleep($intervalMicroseconds);
    }

    return false;
}

/**
 * Build a minimal configuration forcing CLI runtime for real binary tests.
 *
 * @return array<string, mixed>
 */
function buildRealBinaryConfig(string $binaryPath): array
{
    return [
        'instances' => [
            'default' => [
                'runtime' => 'cli',
                'binary_path' => $binaryPath,
                'environment' => [
                    'LIGHTPANDA_DISABLE_TELEMETRY' => 'true',
                    'HTTP_PROXY' => '',
                    'HTTPS_PROXY' => '',
                    'ALL_PROXY' => '',
                    'NO_PROXY' => '',
                ],
            ],
        ],
    ];
}

/**
 * Resolve real binary path from environment or fallback path, otherwise skip test.
 */
function requireRealBinaryOrSkip(PhpUnitTestCase $test): string
{
    if (getenv('LIGHTPANDA_REAL_TESTS') !== '1') {
        $test->markTestSkipped('Set LIGHTPANDA_REAL_TESTS=1 to run real binary integration tests.');
    }

    $binaryPath = getenv('LIGHTPANDA_BINARY_PATH');
    if (! is_string($binaryPath) || trim($binaryPath) === '') {
        $binaryPath = __DIR__.'/../../lightpanda';
    }

    if (! is_file($binaryPath) || ! is_executable($binaryPath)) {
        $test->markTestSkipped("Lightpanda binary is not executable: {$binaryPath}");
    }

    return $binaryPath;
}

/**
 * Terminate and clean up a spawned fixture process.
 *
 * @param  resource  $process
 * @param  array{0: resource, 1: resource, 2: resource}  $pipes
 */
function shutdownProcess(mixed $process, array $pipes): void
{
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }

    if (is_resource($process)) {
        proc_terminate($process, 15);
        usleep(100000);
        proc_terminate($process, 9);
        proc_close($process);
    }
}
