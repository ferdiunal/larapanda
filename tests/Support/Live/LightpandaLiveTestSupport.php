<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Tests\Support\Live;

use Ferdiunal\Larapanda\LarapandaManager;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

/**
 * Shared helpers for opt-in live Lightpanda integration tests.
 */
final class LightpandaLiveTestSupport
{
    /**
     * Resolve live binary path from environment and skip test when unavailable.
     */
    public static function requireLiveBinaryOrSkip(PhpUnitTestCase $test): string
    {
        if (getenv('LIGHTPANDA_LIVE_TESTS') !== '1') {
            $test->markTestSkipped('Set LIGHTPANDA_LIVE_TESTS=1 to run opt-in live integration tests.');
        }

        $binaryPath = getenv('LIGHTPANDA_BINARY_PATH');
        if (! is_string($binaryPath) || trim($binaryPath) === '') {
            $binaryPath = dirname(__DIR__, 3).'/lightpanda';
        }

        if (! is_file($binaryPath) || ! is_executable($binaryPath)) {
            $test->markTestSkipped("Lightpanda binary is not executable: {$binaryPath}");
        }

        return $binaryPath;
    }

    /**
     * Build a CLI-first Larapanda manager for live binary tests.
     */
    public static function managerForBinary(string $binaryPath): LarapandaManager
    {
        return new LarapandaManager([
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
        ]);
    }

    /**
     * Start a temporary local fixture server and return runtime context.
     *
     * @return array{url: string, process: resource, pipes: array{0: resource, 1: resource, 2: resource}, directory: string}
     */
    public static function startFixtureServer(PhpUnitTestCase $test, string $html): array
    {
        $port = random_int(29000, 36000);
        $directory = sys_get_temp_dir().'/larapanda-live-'.bin2hex(random_bytes(6));

        if (! @mkdir($directory) && ! is_dir($directory)) {
            $test->markTestSkipped('Could not create temporary fixture directory.');
        }

        file_put_contents($directory.'/index.html', $html);

        $process = proc_open(
            ['php', '-S', "127.0.0.1:{$port}", '-t', $directory],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (! is_resource($process) || ! isset($pipes[0], $pipes[1], $pipes[2])) {
            $test->markTestSkipped('Could not start local fixture server process.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $ready = self::waitFor(
            timeoutMicroseconds: 4_000_000,
            intervalMicroseconds: 100_000,
            callback: static fn (): bool => is_string(@file_get_contents("http://127.0.0.1:{$port}/")),
        );

        if (! $ready) {
            $stderr = stream_get_contents($pipes[2]);
            self::shutdownProcess($process, [0 => $pipes[0], 1 => $pipes[1], 2 => $pipes[2]], $directory);
            $detail = is_string($stderr) ? trim($stderr) : '';
            $message = $detail !== '' ? $detail : 'fixture server did not become ready';
            $test->markTestSkipped("Local fixture server failed: {$message}");
        }

        /** @var array{0: resource, 1: resource, 2: resource} $stdioPipes */
        $stdioPipes = [0 => $pipes[0], 1 => $pipes[1], 2 => $pipes[2]];

        return [
            'url' => "http://127.0.0.1:{$port}/",
            'process' => $process,
            'pipes' => $stdioPipes,
            'directory' => $directory,
        ];
    }

    /**
     * Stop fixture server process and remove temporary directory.
     *
     * @param  resource  $process
     * @param  array{0: resource, 1: resource, 2: resource}  $pipes
     */
    public static function shutdownProcess(mixed $process, array $pipes, string $directory): void
    {
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($process)) {
            proc_terminate($process, 15);
            usleep(100_000);
            proc_terminate($process, 9);
            proc_close($process);
        }

        self::removeDirectory($directory);
    }

    /**
     * Poll a callback until it returns true or timeout is exceeded.
     */
    public static function waitFor(int $timeoutMicroseconds, int $intervalMicroseconds, callable $callback): bool
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
     * Recursively remove a temporary directory.
     */
    private static function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;
            if (is_dir($path)) {
                self::removeDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
