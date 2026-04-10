<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Requests;

use Ferdiunal\Larapanda\Contracts\LarapandaClientInterface;
use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Enums\LogFormat;
use Ferdiunal\Larapanda\Enums\LogLevel;
use Ferdiunal\Larapanda\Enums\StripMode;
use Ferdiunal\Larapanda\Enums\WaitUntil;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Runtime\FetchResult;
use InvalidArgumentException;

/**
 * Fluent builder for fetch command execution.
 */
final readonly class PendingFetchRequest
{
    public function __construct(
        private LarapandaClientInterface $client,
        private string $url,
        private ?FetchOptions $options = null,
    ) {}

    /**
     * Return a new request instance with method-scoped fetch options.
     *
     * Supports both usage styles:
     * - `withOptions(new FetchOptions(...))`
     * - `withOptions(dump: ..., waitMs: ..., ...)`
     *
     * @param  FetchOptions|bool|null  $insecureDisableTlsHostVerification  Fetch options object or first scalar option.
     * @param  list<string>|null  $logFilterScopes
     * @param  list<StripMode>|null  $stripModes
     *
     * @throws InvalidArgumentException When a FetchOptions object is combined with additional option arguments.
     */
    public function withOptions(
        FetchOptions|bool|null $insecureDisableTlsHostVerification = null,
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
        ?FetchDumpFormat $dump = null,
        ?array $stripModes = null,
        ?bool $withBase = null,
        ?bool $withFrames = null,
        ?int $waitMs = null,
        ?WaitUntil $waitUntil = null,
    ): self {
        if ($insecureDisableTlsHostVerification instanceof FetchOptions) {
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
                $dump,
                $stripModes,
                $withBase,
                $withFrames,
                $waitMs,
                $waitUntil,
            ])) {
                throw new InvalidArgumentException('FetchOptions object cannot be combined with additional withOptions arguments.');
            }

            return new self($this->client, $this->url, $insecureDisableTlsHostVerification);
        }

        $options = ($this->options ?? new FetchOptions)->withOptions(
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
            dump: $dump,
            stripModes: $stripModes,
            withBase: $withBase,
            withFrames: $withFrames,
            waitMs: $waitMs,
            waitUntil: $waitUntil,
        );

        return new self($this->client, $this->url, $options);
    }

    /**
     * Execute the request and return typed fetch output.
     */
    public function run(): FetchResult
    {
        return $this->client->fetch($this->url, $this->options);
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
