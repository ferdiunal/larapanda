<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Exceptions\ProcessExecutionException;
use Ferdiunal\Larapanda\LarapandaClient;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;
use Ferdiunal\Larapanda\Runtime\Command\LightpandaCommandFactory;
use Ferdiunal\Larapanda\Runtime\ProcessResult;
use Ferdiunal\Larapanda\Runtime\RuntimeResolver;
use Ferdiunal\Larapanda\Tests\Support\Fakes\FakeProcessRunner;

/**
 * Verify typed client behavior for fetch, serve, and mcp operations.
 */
/**
 * Ensure successful fetch returns typed output and raw process metadata.
 */
it('fetches content and returns typed process result', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['/opt/lightpanda/lightpanda', 'fetch', 'https://example.com'],
        redactedCommand: ['/opt/lightpanda/lightpanda', 'fetch', 'https://example.com'],
        exitCode: 0,
        stdout: '<!DOCTYPE html><html></html>',
        stderr: '',
        durationSeconds: 0.02,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $result = $client->fetch('https://example.com', new FetchOptions);

    expect($result->output())->toContain('<!DOCTYPE html>')
        ->and($result->process()->isSuccessful())->toBeTrue()
        ->and($runner->runCommands)->toHaveCount(1)
        ->and($runner->runCommands[0])->toContain('fetch');
});

/**
 * Ensure fluent fetch request builder delegates to the same execution pipeline.
 */
it('runs fetch via request builder with ergonomic options', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['/opt/lightpanda/lightpanda', 'fetch', '--dump', 'markdown', 'https://example.com'],
        redactedCommand: ['/opt/lightpanda/lightpanda', 'fetch', '--dump', 'markdown', 'https://example.com'],
        exitCode: 0,
        stdout: '# Example',
        stderr: '',
        durationSeconds: 0.02,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $result = $client->fetchRequest('https://example.com')
        ->withOptions(
            dump: FetchDumpFormat::Markdown,
            waitMs: 1000,
        )
        ->run();

    expect($result->dumpFormat())->toBe(FetchDumpFormat::Markdown)
        ->and($result->asMarkdown())->toContain('# Example')
        ->and($runner->runCommands)->toHaveCount(1);
});

/**
 * Ensure fetch retries failed executions and returns on eventual success.
 */
it('retries failed fetches and eventually succeeds', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(['a'], ['a'], 1, '', 'err', 0.01));
    $runner->queueRunResult(new ProcessResult(['a'], ['a'], 0, 'ok', '', 0.01));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $client->fetch('https://example.com', (new FetchOptions)->withRetries(1));

    expect($runner->runCommands)->toHaveCount(2);
});

/**
 * Ensure non-zero fetch exit codes are surfaced as typed exceptions.
 */
it('throws typed exception when fetch fails', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(['a'], ['a'], 2, '', 'boom', 0.01));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetch('https://example.com'))
        ->toThrow(ProcessExecutionException::class);
});

/**
 * Ensure critical stderr markers are treated as failures even with zero exit code.
 */
it('throws typed exception when fetch has critical stderr markers with zero exit code', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://example.com'],
        redactedCommand: ['lightpanda', 'fetch', 'https://example.com'],
        exitCode: 0,
        stdout: '',
        stderr: '$time=1 $scope=page $level=error $msg="navigate failed" err=CouldntResolveHost',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetch('https://example.com'))
        ->toThrow(ProcessExecutionException::class);
});

/**
 * Ensure non-critical script fetch timeout warnings do not fail successful fetch operations.
 */
it('allows warning-level script fetch timeout markers with zero exit code', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://laravel.com/docs/13.x/ai-sdk'],
        redactedCommand: ['lightpanda', 'fetch', 'https://laravel.com/docs/13.x/ai-sdk'],
        exitCode: 0,
        stdout: '# AI SDK',
        stderr: '$time=1 $scope=http $level=warn $msg="script fetch error" err=OperationTimedout req=https://laravel.com/build/assets/app.js mode=defer kind=module status=0',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $result = $client->fetch('https://laravel.com/docs/13.x/ai-sdk');

    expect($result->process()->isSuccessful())->toBeTrue()
        ->and($result->output())->toContain('# AI SDK');
});

/**
 * Ensure warning-level robots timeout markers do not fail successful fetch operations.
 */
it('allows warning-level robots timeout markers with zero exit code', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://laravel.com/docs/13.x/ai-sdk'],
        redactedCommand: ['lightpanda', 'fetch', 'https://laravel.com/docs/13.x/ai-sdk'],
        exitCode: 0,
        stdout: '# AI SDK',
        stderr: '$time=1 $scope=http $level=warn $msg="robots fetch failed" err=OperationTimedout',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $result = $client->fetch('https://laravel.com/docs/13.x/ai-sdk');

    expect($result->process()->isSuccessful())->toBeTrue()
        ->and($result->output())->toContain('# AI SDK');
});

/**
 * Ensure timeout failures at error level still fail fetch operations.
 */
it('throws when timeout markers are emitted at error level with zero exit code', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://example.com'],
        redactedCommand: ['lightpanda', 'fetch', 'https://example.com'],
        exitCode: 0,
        stdout: '',
        stderr: '$time=1 $scope=http $level=error $msg="robots fetch failed" err=OperationTimedout',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetch('https://example.com'))
        ->toThrow(ProcessExecutionException::class);
});

/**
 * Ensure iframe peer verification navigate errors are treated as non-critical.
 */
it('allows frame-level peer verification navigate errors with zero exit code', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        redactedCommand: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        exitCode: 0,
        stdout: '# Hurriyet',
        stderr: '$time=1 $scope=page $level=error $msg="navigate failed" err=PeerFailedVerification type=frame url=https://example-frame.test',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $result = $client->fetch('https://www.hurriyet.com.tr');

    expect($result->process()->isSuccessful())->toBeTrue()
        ->and($result->output())->toContain('# Hurriyet');
});

/**
 * Ensure peer verification navigate errors remain critical when not frame-scoped.
 */
it('throws on peer verification navigate errors without frame type', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://example.com'],
        redactedCommand: ['lightpanda', 'fetch', 'https://example.com'],
        exitCode: 0,
        stdout: '',
        stderr: '$time=1 $scope=page $level=error $msg="navigate failed" err=PeerFailedVerification url=https://example.com',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetch('https://example.com'))
        ->toThrow(ProcessExecutionException::class);
});

/**
 * Ensure frame-scoped navigate errors remain critical when error marker differs.
 */
it('throws on frame-level navigate errors with non-peer-verification markers', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://example.com'],
        redactedCommand: ['lightpanda', 'fetch', 'https://example.com'],
        exitCode: 0,
        stdout: '',
        stderr: '$time=1 $scope=page $level=error $msg="navigate failed" err=ConnectionFailed type=frame url=https://example-frame.test',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetch('https://example.com'))
        ->toThrow(ProcessExecutionException::class);
});

/**
 * Ensure robots-blocked error-level xhr markers are non-critical when robots compliance is enabled.
 */
it('allows robots-blocked error markers when obeyRobots is enabled', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        redactedCommand: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        exitCode: 0,
        stdout: '# Hurriyet',
        stderr: '$time=1 $scope=http $level=error $msg=error url=https://www.hurriyet.com.tr/api/weather/getweather?cityid=34 err=RobotsBlocked source=xhr.handleError',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $result = $client->fetch(
        'https://www.hurriyet.com.tr',
        (new FetchOptions)->withObeyRobots(true),
    );

    expect($result->process()->isSuccessful())->toBeTrue()
        ->and($result->output())->toContain('# Hurriyet');
});

/**
 * Ensure robots-blocked error-level markers remain critical when robots compliance is disabled.
 */
it('throws on robots-blocked error markers when obeyRobots is disabled', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        redactedCommand: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        exitCode: 0,
        stdout: '',
        stderr: '$time=1 $scope=http $level=error $msg=error url=https://www.hurriyet.com.tr/api/weather/getweather?cityid=34 err=RobotsBlocked source=xhr.handleError',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetch(
        'https://www.hurriyet.com.tr',
        (new FetchOptions)->withObeyRobots(false),
    ))->toThrow(ProcessExecutionException::class);
});

/**
 * Ensure navigate-failed robots-blocked errors stay critical even when robots compliance is enabled.
 */
it('throws on navigate-failed robots-blocked errors even when obeyRobots is enabled', function (): void {
    $runner = new FakeProcessRunner;
    $runner->queueRunResult(new ProcessResult(
        command: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        redactedCommand: ['lightpanda', 'fetch', 'https://www.hurriyet.com.tr'],
        exitCode: 0,
        stdout: '',
        stderr: '$time=1 $scope=page $level=error $msg="navigate failed" err=RobotsBlocked type=frame url=https://example-frame.test',
        durationSeconds: 0.01,
    ));

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetch(
        'https://www.hurriyet.com.tr',
        (new FetchOptions)->withObeyRobots(true),
    ))->toThrow(ProcessExecutionException::class);
});

/**
 * Ensure long-running commands are started through the process runner abstraction.
 */
it('starts serve and mcp commands via process runner', function (): void {
    $runner = new FakeProcessRunner;

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $serveHandle = $client->serve(new ServeOptions);
    $mcpHandle = $client->mcp(new McpOptions);

    expect($serveHandle->command())->toContain('serve')
        ->and($mcpHandle->command())->toContain('mcp')
        ->and($runner->startCommands)->toHaveCount(2);
});

/**
 * Ensure serve and MCP request builders delegate to long-running process startup.
 */
it('starts serve and mcp via request builders', function (): void {
    $runner = new FakeProcessRunner;

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $serveHandle = $client->serveRequest()
        ->withOptions(port: 9333)
        ->run();

    $mcpHandle = $client->mcpRequest()
        ->withOptions(retries: 1)
        ->run();

    expect($serveHandle->command())->toContain('serve')
        ->and($mcpHandle->command())->toContain('mcp')
        ->and($runner->startCommands)->toHaveCount(2);
});

/**
 * Ensure request builders still accept explicit pre-built options objects.
 */
it('keeps backward compatibility for object-based withOptions on all builders', function (): void {
    $runner = new FakeProcessRunner;

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $fetch = $client->fetchRequest('https://example.com')
        ->withOptions((new FetchOptions)->withOptions(dump: FetchDumpFormat::Markdown))
        ->run();

    $serveHandle = $client->serveRequest()
        ->withOptions((new ServeOptions)->withOptions(port: 9333))
        ->run();

    $mcpHandle = $client->mcpRequest()
        ->withOptions((new McpOptions)->withOptions(retries: 1))
        ->run();

    expect($fetch->dumpFormat())->toBe(FetchDumpFormat::Markdown)
        ->and($serveHandle->command())->toContain('serve')
        ->and($mcpHandle->command())->toContain('mcp')
        ->and($runner->runCommands)->toHaveCount(1)
        ->and($runner->startCommands)->toHaveCount(2);
});

/**
 * Ensure mixing option object and named arguments is rejected explicitly.
 */
it('throws when object-based and named-argument styles are mixed', function (): void {
    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: new FakeProcessRunner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    expect(fn (): mixed => $client->fetchRequest('https://example.com')
        ->withOptions(new FetchOptions, dump: FetchDumpFormat::Markdown))
        ->toThrow(InvalidArgumentException::class);

    expect(fn (): mixed => $client->serveRequest()
        ->withOptions(new ServeOptions, port: 9333))
        ->toThrow(InvalidArgumentException::class);

    expect(fn (): mixed => $client->mcpRequest()
        ->withOptions(new McpOptions, retries: 1))
        ->toThrow(InvalidArgumentException::class);
});

/**
 * Ensure chained builder option updates merge immutably and keep previous builder instances unchanged.
 */
it('merges chained builder options immutably', function (): void {
    $runner = new FakeProcessRunner;

    $client = new LarapandaClient(
        profile: InstanceProfile::fromArray('default', [
            'runtime' => 'auto',
            'binary_path' => '/opt/lightpanda/lightpanda',
        ]),
        processRunner: $runner,
        runtimeResolver: new RuntimeResolver(
            isCommandAvailable: static fn (): bool => true,
            isBinaryExecutable: static fn (): bool => true,
        ),
        commandFactory: new LightpandaCommandFactory,
    );

    $baseRequest = $client->fetchRequest('https://example.com');
    $mergedRequest = $baseRequest
        ->withOptions(dump: FetchDumpFormat::Markdown)
        ->withOptions(obeyRobots: true, waitMs: 1000);

    $baseRequest->run();
    $result = $mergedRequest->run();

    $baseCommand = $runner->runCommands[0];
    $mergedCommand = $runner->runCommands[1];

    expect($baseCommand)->not->toContain('--dump')
        ->and($baseCommand)->not->toContain('--obey-robots')
        ->and($mergedCommand)->toContain('--dump')
        ->and($mergedCommand)->toContain('markdown')
        ->and($mergedCommand)->toContain('--obey-robots')
        ->and($mergedCommand)->toContain('--wait-ms')
        ->and($mergedCommand)->toContain('1000')
        ->and($result->dumpFormat())->toBe(FetchDumpFormat::Markdown)
        ->and($runner->runCommands)->toHaveCount(2);
});
