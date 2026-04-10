<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Integrations\Mcp\LarapandaMcpServer;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolCatalog;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolInputValidator;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolInvoker;
use Ferdiunal\Larapanda\Integrations\Mcp\McpLightpandaToolInvoker;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\GotoTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\MarkdownTool;
use Ferdiunal\Larapanda\Tests\Support\Fakes\FakeMcpBridgeClient;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server;

/**
 * Determine whether real Laravel MCP runtime primitives are available.
 */
function larapandaSupportsLaravelMcpRuntime(): bool
{
    return class_exists(Server::class)
        && class_exists(Mcp::class)
        && class_exists(Response::class);
}

/**
 * Build a temporary server class for docs-style Server::tool(...) tests.
 *
 * @param  list<class-string>  $toolClasses
 * @return class-string
 */
function larapandaMakeDocsStyleMcpServer(array $toolClasses): string
{
    static $classMap = [];

    $normalizedTools = array_values(array_map(
        static fn (string $toolClass): string => ltrim($toolClass, '\\'),
        $toolClasses,
    ));

    $cacheKey = sha1(implode('|', $normalizedTools));
    if (isset($classMap[$cacheKey])) {
        return $classMap[$cacheKey];
    }

    $suffix = strtoupper(substr($cacheKey, 0, 12));
    $namespace = 'Ferdiunal\\Larapanda\\Tests\\Support\\Mcp';
    $shortClass = "DocsStyleMcpServer{$suffix}";
    $fqcn = "{$namespace}\\{$shortClass}";

    if (! class_exists($fqcn, false)) {
        $toolsLiteral = implode(
            ', ',
            array_map(
                static fn (string $toolClass): string => '\\'.ltrim($toolClass, '\\').'::class',
                $normalizedTools,
            ),
        );

        $classBody = <<<PHP
namespace {$namespace};

final class {$shortClass} extends \\Laravel\\Mcp\\Server
{
    protected array \$tools = [{$toolsLiteral}];
}
PHP;

        eval($classBody);
    }

    $classMap[$cacheKey] = $fqcn;

    return $fqcn;
}

beforeEach(function (): void {
    if (! larapandaSupportsLaravelMcpRuntime()) {
        $this->markTestSkipped('Laravel MCP package is not installed; skipping MCP contract tests.');
    }

    config()->set('app.debug', false);

    $bridge = new FakeMcpBridgeClient(['ok' => true]);

    app()->instance(
        McpLightpandaToolInvoker::class,
        new McpLightpandaToolInvoker(
            new LightpandaToolInvoker(
                bridgeClient: $bridge,
                catalog: new LightpandaToolCatalog,
                validator: new LightpandaToolInputValidator,
            ),
        ),
    );
});

/**
 * Ensure local registration uses Laravel MCP local-server contract.
 */
it('registers local server handles through laravel mcp facade', function (): void {
    LarapandaMcpServer::registerLocal('larapanda-contract', [MarkdownTool::class]);

    $registered = Mcp::getLocalServer('larapanda-contract');

    expect($registered)->toBeCallable();
});

/**
 * Verify docs-style server primitive tests for successful tool execution.
 */
it('supports docs-style tool assertions for successful responses', function (): void {
    $serverClass = larapandaMakeDocsStyleMcpServer([MarkdownTool::class]);

    $response = $serverClass::tool(MarkdownTool::class, [
        'url' => 'https://example.com',
        'session_id' => 'docs-session',
    ]);

    $response
        ->assertOk()
        ->assertSee('ok');
});

/**
 * Verify docs-style server primitive tests for validation failures.
 */
it('supports docs-style tool assertions for validation errors', function (): void {
    $serverClass = larapandaMakeDocsStyleMcpServer([GotoTool::class]);

    $response = $serverClass::tool(GotoTool::class, []);

    $response->assertHasErrors([
        'Required tool argument [url] is missing',
    ]);
});
