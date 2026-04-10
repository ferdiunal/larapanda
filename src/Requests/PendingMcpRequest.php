<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Requests;

use Ferdiunal\Larapanda\Contracts\LarapandaClientInterface;
use Ferdiunal\Larapanda\Enums\LogFormat;
use Ferdiunal\Larapanda\Enums\LogLevel;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Runtime\RunningInstanceHandle;
use InvalidArgumentException;

/**
 * Fluent builder for MCP command execution.
 */
final readonly class PendingMcpRequest
{
    public function __construct(
        private LarapandaClientInterface $client,
        private ?McpOptions $options = null,
    ) {}

    /**
     * Return a new request instance with method-scoped MCP options.
     *
     * Supports both usage styles:
     * - `withOptions(new McpOptions(...))`
     * - `withOptions(httpProxy: ..., retries: ..., ...)`
     *
     * @param  McpOptions|bool|null  $insecureDisableTlsHostVerification  MCP options object or first scalar option.
     * @param  list<string>|null  $logFilterScopes
     *
     * @throws InvalidArgumentException When a McpOptions object is combined with additional option arguments.
     */
    public function withOptions(
        McpOptions|bool|null $insecureDisableTlsHostVerification = null,
        ?bool $obeyRobots = null,
        ?string $httpProxy = null,
        ?string $proxyBearerToken = null,
        ?int $httpMaxConcurrent = null,
        ?int $httpMaxHostOpen = null,
        ?int $httpConnectTimeout = null,
        ?int $httpTimeout = null,
        ?int $httpMaxResponseSize = null,
        ?LogLevel $logLevel = null,
        ?LogFormat $logFormat = null,
        ?array $logFilterScopes = null,
        ?string $userAgentSuffix = null,
        ?string $webBotAuthKeyFile = null,
        ?string $webBotAuthKeyId = null,
        ?string $webBotAuthDomain = null,
        ?int $retries = null,
        ?float $executionTimeoutSeconds = null,
    ): self {
        if ($insecureDisableTlsHostVerification instanceof McpOptions) {
            if (self::hasNonNullArguments([
                $obeyRobots,
                $httpProxy,
                $proxyBearerToken,
                $httpMaxConcurrent,
                $httpMaxHostOpen,
                $httpConnectTimeout,
                $httpTimeout,
                $httpMaxResponseSize,
                $logLevel,
                $logFormat,
                $logFilterScopes,
                $userAgentSuffix,
                $webBotAuthKeyFile,
                $webBotAuthKeyId,
                $webBotAuthDomain,
                $retries,
                $executionTimeoutSeconds,
            ])) {
                throw new InvalidArgumentException('McpOptions object cannot be combined with additional withOptions arguments.');
            }

            return new self($this->client, $insecureDisableTlsHostVerification);
        }

        $options = ($this->options ?? new McpOptions)->withOptions(
            insecureDisableTlsHostVerification: $insecureDisableTlsHostVerification,
            obeyRobots: $obeyRobots,
            httpProxy: $httpProxy,
            proxyBearerToken: $proxyBearerToken,
            httpMaxConcurrent: $httpMaxConcurrent,
            httpMaxHostOpen: $httpMaxHostOpen,
            httpConnectTimeout: $httpConnectTimeout,
            httpTimeout: $httpTimeout,
            httpMaxResponseSize: $httpMaxResponseSize,
            logLevel: $logLevel,
            logFormat: $logFormat,
            logFilterScopes: $logFilterScopes,
            userAgentSuffix: $userAgentSuffix,
            webBotAuthKeyFile: $webBotAuthKeyFile,
            webBotAuthKeyId: $webBotAuthKeyId,
            webBotAuthDomain: $webBotAuthDomain,
            retries: $retries,
            executionTimeoutSeconds: $executionTimeoutSeconds,
        );

        return new self($this->client, $options);
    }

    /**
     * Execute the request and return a running process handle.
     */
    public function run(): RunningInstanceHandle
    {
        return $this->client->mcp($this->options);
    }

    /**
     * @param  list<mixed>  $arguments
     */
    private static function hasNonNullArguments(array $arguments): bool
    {
        foreach ($arguments as $argument) {
            if ($argument !== null) {
                return true;
            }
        }

        return false;
    }
}
