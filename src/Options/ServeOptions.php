<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Options;

use Ferdiunal\Larapanda\Enums\LogFormat;
use Ferdiunal\Larapanda\Enums\LogLevel;
use InvalidArgumentException;

/**
 * Immutable method-scoped options for `lightpanda serve`.
 */
final class ServeOptions extends AbstractLightpandaOptions
{
    /** Bind host used by the CDP server. */
    private string $host = '127.0.0.1';

    /** Bind port used by the CDP server. */
    private int $port = 9222;

    /** Optional public host advertised by the CDP endpoint. */
    private ?string $advertiseHost = null;

    /** Serve timeout in seconds. */
    private int $timeoutSeconds = 10;

    /** Maximum concurrent CDP client connections. */
    private int $cdpMaxConnections = 16;

    /** Maximum queued pending CDP connections. */
    private int $cdpMaxPendingConnections = 128;

    /**
     * Return a cloned serve options instance with common and serve-specific settings applied.
     *
     * Null values are ignored and do not mutate current state.
     *
     * @param  list<string>|null  $logFilterScopes
     */
    public function withOptions(
        ?bool $insecureDisableTlsHostVerification = null,
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
        ?string $host = null,
        ?int $port = null,
        ?string $advertiseHost = null,
        ?int $timeoutSeconds = null,
        ?int $cdpMaxConnections = null,
        ?int $cdpMaxPendingConnections = null,
    ): static {
        $options = parent::withOptions(
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

        if ($host !== null) {
            $options = $options->withHost($host);
        }

        if ($port !== null) {
            $options = $options->withPort($port);
        }

        if ($advertiseHost !== null) {
            $options = $options->withAdvertiseHost($advertiseHost);
        }

        if ($timeoutSeconds !== null) {
            $options = $options->withTimeoutSeconds($timeoutSeconds);
        }

        if ($cdpMaxConnections !== null) {
            $options = $options->withCdpMaxConnections($cdpMaxConnections);
        }

        if ($cdpMaxPendingConnections !== null) {
            $options = $options->withCdpMaxPendingConnections($cdpMaxPendingConnections);
        }

        return $options;
    }

    /**
     * Return a cloned serve options instance with bind host.
     *
     * @throws InvalidArgumentException When `$host` is empty after trimming.
     */
    public function withHost(string $host): self
    {
        $host = trim($host);

        if ($host === '') {
            throw new InvalidArgumentException('host cannot be empty.');
        }

        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Return a cloned serve options instance with bind port.
     *
     * @throws InvalidArgumentException When `$port` is outside the valid TCP range.
     */
    public function withPort(int $port): self
    {
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('port must be between 1 and 65535.');
        }

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Return a cloned serve options instance with advertised host override.
     */
    public function withAdvertiseHost(?string $advertiseHost): self
    {
        $normalized = $advertiseHost !== null ? trim($advertiseHost) : null;

        if ($normalized === '') {
            $normalized = null;
        }

        $clone = clone $this;
        $clone->advertiseHost = $normalized;

        return $clone;
    }

    /**
     * Return a cloned serve options instance with serve timeout.
     *
     * @throws InvalidArgumentException When `$timeoutSeconds` is outside the accepted range.
     */
    public function withTimeoutSeconds(int $timeoutSeconds): self
    {
        if ($timeoutSeconds < 1 || $timeoutSeconds > 604800) {
            throw new InvalidArgumentException('timeoutSeconds must be between 1 and 604800.');
        }

        $clone = clone $this;
        $clone->timeoutSeconds = $timeoutSeconds;

        return $clone;
    }

    /**
     * Return a cloned serve options instance with max CDP connections.
     *
     * @throws InvalidArgumentException When `$max` is lower than 1.
     */
    public function withCdpMaxConnections(int $max): self
    {
        if ($max < 1) {
            throw new InvalidArgumentException('cdpMaxConnections must be greater than 0.');
        }

        $clone = clone $this;
        $clone->cdpMaxConnections = $max;

        return $clone;
    }

    /**
     * Return a cloned serve options instance with max pending CDP connections.
     *
     * @throws InvalidArgumentException When `$max` is lower than 1.
     */
    public function withCdpMaxPendingConnections(int $max): self
    {
        if ($max < 1) {
            throw new InvalidArgumentException('cdpMaxPendingConnections must be greater than 0.');
        }

        $clone = clone $this;
        $clone->cdpMaxPendingConnections = $max;

        return $clone;
    }

    /**
     * Return configured bind host.
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * Return configured bind port.
     */
    public function port(): int
    {
        return $this->port;
    }

    /**
     * Return configured advertised host override.
     */
    public function advertiseHost(): ?string
    {
        return $this->advertiseHost;
    }

    /**
     * Return configured serve timeout in seconds.
     */
    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    /**
     * Return configured max CDP connections.
     */
    public function cdpMaxConnections(): int
    {
        return $this->cdpMaxConnections;
    }

    /**
     * Return configured max pending CDP connections.
     */
    public function cdpMaxPendingConnections(): int
    {
        return $this->cdpMaxPendingConnections;
    }
}
