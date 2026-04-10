<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda;

use Ferdiunal\Larapanda\Commands\LarapandaCommand;
use Ferdiunal\Larapanda\Contracts\LarapandaClientInterface;
use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;
use Ferdiunal\Larapanda\Integrations\Ai\AiLightpandaToolInvoker;
use Ferdiunal\Larapanda\Integrations\Ai\LarapandaAiTools;
use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpBridgeClientInterface;
use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpSessionManagerInterface;
use Ferdiunal\Larapanda\Integrations\Mcp\InMemoryMcpSessionManager;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolCatalog;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolInputValidator;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolInvoker;
use Ferdiunal\Larapanda\Integrations\Mcp\McpLightpandaToolInvoker;
use Ferdiunal\Larapanda\Integrations\Mcp\StdioMcpBridgeClient;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Runtime\NativeProcessRunner;
use Ferdiunal\Larapanda\Runtime\ProcessRunner;
use Laravel\Ai\Contracts\Tool;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Laravel package service provider for Larapanda container bindings and assets.
 */
final class LarapandaServiceProvider extends PackageServiceProvider
{
    /**
     * Register package metadata, config publishing, and artisan commands.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('larapanda')
            ->hasConfigFile()
            ->hasCommand(LarapandaCommand::class);
    }

    /**
     * Register runtime services and container aliases.
     */
    public function packageRegistered(): void
    {
        $this->app->singleton(ProcessRunner::class, static fn (): ProcessRunner => new NativeProcessRunner);

        $this->app->singleton(LarapandaManagerInterface::class, function ($app): LarapandaManagerInterface {
            $config = (array) $app['config']->get('larapanda', []);

            return new LarapandaManager($config, $app->make(ProcessRunner::class));
        });

        $this->app->alias(LarapandaManagerInterface::class, LarapandaManager::class);
        $this->app->singleton('larapanda', static fn ($app): LarapandaManagerInterface => $app->make(LarapandaManagerInterface::class));

        $this->app->bind(LarapandaClientInterface::class, static fn ($app): LarapandaClientInterface => $app->make(LarapandaManagerInterface::class)->instance());

        $this->registerIntegrationServices();
    }

    /**
     * Register optional AI SDK and MCP bridge services.
     */
    private function registerIntegrationServices(): void
    {
        $this->app->singleton(LightpandaToolCatalog::class, static fn (): LightpandaToolCatalog => new LightpandaToolCatalog);
        $this->app->singleton(LightpandaToolInputValidator::class, static fn (): LightpandaToolInputValidator => new LightpandaToolInputValidator);

        $this->app->singleton('larapanda.integrations.ai.session_manager', function ($app): McpSessionManagerInterface {
            $config = (array) $app['config']->get('larapanda.integrations.ai', []);

            return new InMemoryMcpSessionManager(
                manager: $app->make(LarapandaManagerInterface::class),
                instance: (string) ($config['instance'] ?? 'default'),
                sessionTtlSeconds: (int) ($config['session_ttl_seconds'] ?? 300),
                maxSessions: (int) ($config['max_sessions'] ?? 16),
                defaultSessionId: (string) ($config['default_session_id'] ?? 'default'),
                options: $this->buildIntegrationMcpOptions($config),
            );
        });

        $this->app->singleton('larapanda.integrations.ai.bridge', function ($app): McpBridgeClientInterface {
            $config = (array) $app['config']->get('larapanda.integrations.ai', []);

            return new StdioMcpBridgeClient(
                sessions: $app->make('larapanda.integrations.ai.session_manager'),
                requestTimeoutSeconds: (float) ($config['request_timeout_seconds'] ?? 15.0),
            );
        });

        $this->app->singleton('larapanda.integrations.ai.invoker', function ($app): AiLightpandaToolInvoker {
            return new AiLightpandaToolInvoker(
                new LightpandaToolInvoker(
                    bridgeClient: $app->make('larapanda.integrations.ai.bridge'),
                    catalog: $app->make(LightpandaToolCatalog::class),
                    validator: $app->make(LightpandaToolInputValidator::class),
                )
            );
        });

        $this->app->singleton('larapanda.integrations.mcp.session_manager', function ($app): McpSessionManagerInterface {
            $config = (array) $app['config']->get('larapanda.integrations.mcp', []);

            return new InMemoryMcpSessionManager(
                manager: $app->make(LarapandaManagerInterface::class),
                instance: (string) ($config['instance'] ?? 'mcp'),
                sessionTtlSeconds: (int) ($config['session_ttl_seconds'] ?? 300),
                maxSessions: (int) ($config['max_sessions'] ?? 32),
                defaultSessionId: (string) ($config['default_session_id'] ?? 'default'),
                options: $this->buildIntegrationMcpOptions($config),
            );
        });

        $this->app->singleton('larapanda.integrations.mcp.bridge', function ($app): McpBridgeClientInterface {
            $config = (array) $app['config']->get('larapanda.integrations.mcp', []);

            return new StdioMcpBridgeClient(
                sessions: $app->make('larapanda.integrations.mcp.session_manager'),
                requestTimeoutSeconds: (float) ($config['request_timeout_seconds'] ?? 15.0),
            );
        });

        $this->app->singleton(McpLightpandaToolInvoker::class, function ($app): McpLightpandaToolInvoker {
            return new McpLightpandaToolInvoker(
                new LightpandaToolInvoker(
                    bridgeClient: $app->make('larapanda.integrations.mcp.bridge'),
                    catalog: $app->make(LightpandaToolCatalog::class),
                    validator: $app->make(LightpandaToolInputValidator::class),
                )
            );
        });

        $this->app->bind(McpSessionManagerInterface::class, static fn ($app): McpSessionManagerInterface => $app->make('larapanda.integrations.mcp.session_manager'));
        $this->app->bind(McpBridgeClientInterface::class, static fn ($app): McpBridgeClientInterface => $app->make('larapanda.integrations.mcp.bridge'));

        if (interface_exists(Tool::class)) {
            $this->app->singleton(LarapandaAiTools::class, function ($app): LarapandaAiTools {
                $config = (array) $app['config']->get('larapanda.integrations.ai', []);

                return new LarapandaAiTools(
                    catalog: $app->make(LightpandaToolCatalog::class),
                    invoker: $app->make('larapanda.integrations.ai.invoker'),
                    namePrefix: (string) ($config['tool_prefix'] ?? 'lightpanda_'),
                    exposedTools: array_values(array_filter((array) ($config['exposed_tools'] ?? []), 'is_string')),
                );
            });
        }
    }

    /**
     * Build MCP command options from integration configuration.
     *
     * @param  array<string, mixed>  $config
     */
    private function buildIntegrationMcpOptions(array $config): McpOptions
    {
        $options = new McpOptions;

        $obeyRobots = $config['obey_robots'] ?? null;
        $httpProxy = $config['http_proxy'] ?? null;
        $proxyBearerToken = $config['proxy_bearer_token'] ?? null;
        $insecureDisableTlsHostVerification = $config['insecure_disable_tls_host_verification'] ?? null;
        $retries = $config['retries'] ?? null;
        $executionTimeoutSeconds = $config['execution_timeout_seconds'] ?? null;

        return $options->withOptions(
            insecureDisableTlsHostVerification: is_bool($insecureDisableTlsHostVerification) ? $insecureDisableTlsHostVerification : null,
            obeyRobots: is_bool($obeyRobots) ? $obeyRobots : null,
            httpProxy: is_string($httpProxy) ? $httpProxy : null,
            proxyBearerToken: is_string($proxyBearerToken) ? $proxyBearerToken : null,
            retries: is_int($retries) ? $retries : null,
            executionTimeoutSeconds: is_int($executionTimeoutSeconds) || is_float($executionTimeoutSeconds) ? (float) $executionTimeoutSeconds : null,
        );
    }
}
