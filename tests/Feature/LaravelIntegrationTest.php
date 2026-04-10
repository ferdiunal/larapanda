<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Contracts\LarapandaClientInterface;
use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;
use Ferdiunal\Larapanda\Facades\Larapanda as LarapandaFacade;
use Ferdiunal\Larapanda\Integrations\Ai\LarapandaAiTools;
use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpBridgeClientInterface;
use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpSessionManagerInterface;
use Ferdiunal\Larapanda\Integrations\Mcp\LarapandaMcpTools;
use Ferdiunal\Larapanda\LarapandaManager;
use Laravel\Ai\Contracts\Tool;

/**
 * Validate Laravel service container and facade integration.
 */
/**
 * Ensure manager bindings and facade root resolve to the same singleton instance.
 */
it('registers manager in the service container and facade', function (): void {
    $manager = app(LarapandaManagerInterface::class);

    expect($manager)->toBeInstanceOf(LarapandaManager::class)
        ->and(app('larapanda'))->toBe($manager)
        ->and(LarapandaFacade::getFacadeRoot())->toBe($manager);
});

/**
 * Ensure package config is loaded with named instance structure.
 */
it('loads default larapanda config with named instances', function (): void {
    expect(config('larapanda.default_instance'))->toBe('default')
        ->and(config('larapanda.instances.default'))->toBeArray();
});

/**
 * Ensure default client binding resolves against configured named default instance.
 */
it('resolves default client binding using configured default instance profile', function (): void {
    $client = app(LarapandaClientInterface::class);

    expect($client->profile()->name)->toBe((string) config('larapanda.default_instance'));
});

/**
 * Ensure MCP integration contracts are container-bound regardless of optional package installation.
 */
it('registers mcp bridge and session manager bindings', function (): void {
    expect(app()->bound(McpBridgeClientInterface::class))->toBeTrue()
        ->and(app()->bound(McpSessionManagerInterface::class))->toBeTrue();
});

/**
 * Ensure AI and MCP integration config contracts are loaded with array payloads.
 */
it('loads integration config blocks for ai and mcp adapters', function (): void {
    expect(config('larapanda.integrations.ai'))->toBeArray()
        ->and(config('larapanda.integrations.mcp'))->toBeArray();
});

/**
 * Ensure AI registry binding is conditional on Laravel AI contract availability.
 */
it('conditionally registers ai tool registry when laravel ai is installed', function (): void {
    $shouldBeBound = interface_exists(Tool::class);

    expect(app()->bound(LarapandaAiTools::class))->toBe($shouldBeBound);
});

/**
 * Ensure canonical MCP tool registry exposes all full-v1 tool adapters.
 */
it('exposes canonical full-scope mcp tool class registry', function (): void {
    expect(LarapandaMcpTools::all())->toHaveCount(20);
});
