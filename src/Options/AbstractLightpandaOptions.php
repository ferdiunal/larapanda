<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Options;

use Ferdiunal\Larapanda\Enums\LogFormat;
use Ferdiunal\Larapanda\Enums\LogLevel;
use InvalidArgumentException;

/**
 * Shared immutable option bag for all Lightpanda commands.
 */
abstract class AbstractLightpandaOptions
{
    /** Disable TLS host verification for outbound HTTP requests. */
    protected bool $insecureDisableTlsHostVerification = false;

    /** Respect `robots.txt` directives during navigation. */
    protected bool $obeyRobots = false;

    /** HTTP proxy URL passed to Lightpanda. */
    protected ?string $httpProxy = null;

    /** Bearer token used for proxy authentication. */
    protected ?string $proxyBearerToken = null;

    /** Maximum number of concurrent HTTP requests. */
    protected int $httpMaxConcurrent = 10;

    /** Maximum number of open connections per host. */
    protected int $httpMaxHostOpen = 4;

    /** HTTP connect timeout in milliseconds. */
    protected int $httpConnectTimeout = 0;

    /** HTTP request timeout in milliseconds. */
    protected int $httpTimeout = 10000;

    /** Optional upper bound for HTTP response size in bytes. */
    protected ?int $httpMaxResponseSize = null;

    /** Selected log level for Lightpanda runtime logs. */
    protected LogLevel $logLevel = LogLevel::Warn;

    /** Selected output encoding for Lightpanda runtime logs. */
    protected LogFormat $logFormat = LogFormat::Logfmt;

    /** @var list<string> */
    protected array $logFilterScopes = [];

    /** Optional suffix appended to the Lightpanda user agent. */
    protected ?string $userAgentSuffix = null;

    /** Path to WebBot authentication key file. */
    protected ?string $webBotAuthKeyFile = null;

    /** WebBot authentication key identifier. */
    protected ?string $webBotAuthKeyId = null;

    /** WebBot authentication domain. */
    protected ?string $webBotAuthDomain = null;

    /** Retry count used by the SDK wrapper. */
    protected int $retries = 0;

    /** Optional wall-clock timeout for command execution in seconds. */
    protected ?float $executionTimeoutSeconds = null;

    /**
     * Return a cloned options instance with multiple common settings applied.
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
    ): static {
        $options = $this;

        if ($insecureDisableTlsHostVerification !== null) {
            $options = $options->withInsecureDisableTlsHostVerification($insecureDisableTlsHostVerification);
        }

        if ($obeyRobots !== null) {
            $options = $options->withObeyRobots($obeyRobots);
        }

        if ($httpProxy !== null) {
            $options = $options->withHttpProxy($httpProxy);
        }

        if ($proxyBearerToken !== null) {
            $options = $options->withProxyBearerToken($proxyBearerToken);
        }

        if ($httpMaxConcurrent !== null) {
            $options = $options->withHttpMaxConcurrent($httpMaxConcurrent);
        }

        if ($httpMaxHostOpen !== null) {
            $options = $options->withHttpMaxHostOpen($httpMaxHostOpen);
        }

        if ($httpConnectTimeout !== null) {
            $options = $options->withHttpConnectTimeout($httpConnectTimeout);
        }

        if ($httpTimeout !== null) {
            $options = $options->withHttpTimeout($httpTimeout);
        }

        if ($httpMaxResponseSize !== null) {
            $options = $options->withHttpMaxResponseSize($httpMaxResponseSize);
        }

        if ($logLevel !== null) {
            $options = $options->withLogLevel($logLevel);
        }

        if ($logFormat !== null) {
            $options = $options->withLogFormat($logFormat);
        }

        if ($logFilterScopes !== null) {
            $options = $options->withLogFilterScopes($logFilterScopes);
        }

        if ($userAgentSuffix !== null) {
            $options = $options->withUserAgentSuffix($userAgentSuffix);
        }

        if ($webBotAuthKeyFile !== null) {
            $options = $options->withWebBotAuthKeyFile($webBotAuthKeyFile);
        }

        if ($webBotAuthKeyId !== null) {
            $options = $options->withWebBotAuthKeyId($webBotAuthKeyId);
        }

        if ($webBotAuthDomain !== null) {
            $options = $options->withWebBotAuthDomain($webBotAuthDomain);
        }

        if ($retries !== null) {
            $options = $options->withRetries($retries);
        }

        if ($executionTimeoutSeconds !== null) {
            $options = $options->withExecutionTimeoutSeconds($executionTimeoutSeconds);
        }

        return $options;
    }

    /**
     * Return a cloned options instance with TLS host verification disabled or enabled.
     */
    public function withInsecureDisableTlsHostVerification(bool $enabled = true): static
    {
        $clone = clone $this;
        $clone->insecureDisableTlsHostVerification = $enabled;

        return $clone;
    }

    /**
     * Return a cloned options instance with robots policy handling updated.
     */
    public function withObeyRobots(bool $enabled = true): static
    {
        $clone = clone $this;
        $clone->obeyRobots = $enabled;

        return $clone;
    }

    /**
     * Return a cloned options instance with an HTTP proxy endpoint.
     */
    public function withHttpProxy(?string $httpProxy): static
    {
        $clone = clone $this;
        $clone->httpProxy = self::normalizeNullableString($httpProxy);

        return $clone;
    }

    /**
     * Return a cloned options instance with a proxy bearer token.
     */
    public function withProxyBearerToken(?string $token): static
    {
        $clone = clone $this;
        $clone->proxyBearerToken = self::normalizeNullableString($token);

        return $clone;
    }

    /**
     * Return a cloned options instance with max concurrent HTTP requests.
     *
     * @throws InvalidArgumentException When `$max` is lower than 1.
     */
    public function withHttpMaxConcurrent(int $max): static
    {
        if ($max < 1) {
            throw new InvalidArgumentException('httpMaxConcurrent must be greater than 0.');
        }

        $clone = clone $this;
        $clone->httpMaxConcurrent = $max;

        return $clone;
    }

    /**
     * Return a cloned options instance with per-host connection limit.
     *
     * @throws InvalidArgumentException When `$max` is lower than 1.
     */
    public function withHttpMaxHostOpen(int $max): static
    {
        if ($max < 1) {
            throw new InvalidArgumentException('httpMaxHostOpen must be greater than 0.');
        }

        $clone = clone $this;
        $clone->httpMaxHostOpen = $max;

        return $clone;
    }

    /**
     * Return a cloned options instance with HTTP connect timeout.
     *
     * @throws InvalidArgumentException When `$milliseconds` is negative.
     */
    public function withHttpConnectTimeout(int $milliseconds): static
    {
        if ($milliseconds < 0) {
            throw new InvalidArgumentException('httpConnectTimeout must be greater than or equal to 0.');
        }

        $clone = clone $this;
        $clone->httpConnectTimeout = $milliseconds;

        return $clone;
    }

    /**
     * Return a cloned options instance with HTTP request timeout.
     *
     * @throws InvalidArgumentException When `$milliseconds` is negative.
     */
    public function withHttpTimeout(int $milliseconds): static
    {
        if ($milliseconds < 0) {
            throw new InvalidArgumentException('httpTimeout must be greater than or equal to 0.');
        }

        $clone = clone $this;
        $clone->httpTimeout = $milliseconds;

        return $clone;
    }

    /**
     * Return a cloned options instance with HTTP max response size.
     *
     * @throws InvalidArgumentException When `$bytes` is not null and lower than 1.
     */
    public function withHttpMaxResponseSize(?int $bytes): static
    {
        if ($bytes !== null && $bytes < 1) {
            throw new InvalidArgumentException('httpMaxResponseSize must be null or greater than 0.');
        }

        $clone = clone $this;
        $clone->httpMaxResponseSize = $bytes;

        return $clone;
    }

    /**
     * Return a cloned options instance with the selected log level.
     */
    public function withLogLevel(LogLevel $logLevel): static
    {
        $clone = clone $this;
        $clone->logLevel = $logLevel;

        return $clone;
    }

    /**
     * Return a cloned options instance with the selected log format.
     */
    public function withLogFormat(LogFormat $logFormat): static
    {
        $clone = clone $this;
        $clone->logFormat = $logFormat;

        return $clone;
    }

    /**
     * Return a cloned options instance with normalized log filter scopes.
     *
     * @param  list<string>  $scopes
     */
    public function withLogFilterScopes(array $scopes): static
    {
        $normalized = [];

        foreach ($scopes as $scope) {
            $trimmed = trim($scope);

            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        $clone = clone $this;
        $clone->logFilterScopes = array_values(array_unique($normalized));

        return $clone;
    }

    /**
     * Return a cloned options instance with one additional log filter scope.
     */
    public function withLogFilterScope(string $scope): static
    {
        return $this->withLogFilterScopes([...$this->logFilterScopes, $scope]);
    }

    /**
     * Return a cloned options instance with user-agent suffix.
     */
    public function withUserAgentSuffix(?string $suffix): static
    {
        $clone = clone $this;
        $clone->userAgentSuffix = self::normalizeNullableString($suffix);

        return $clone;
    }

    /**
     * Return a cloned options instance with WebBot auth key file path.
     */
    public function withWebBotAuthKeyFile(?string $path): static
    {
        $clone = clone $this;
        $clone->webBotAuthKeyFile = self::normalizeNullableString($path);

        return $clone;
    }

    /**
     * Return a cloned options instance with WebBot auth key id.
     */
    public function withWebBotAuthKeyId(?string $keyId): static
    {
        $clone = clone $this;
        $clone->webBotAuthKeyId = self::normalizeNullableString($keyId);

        return $clone;
    }

    /**
     * Return a cloned options instance with WebBot auth domain.
     */
    public function withWebBotAuthDomain(?string $domain): static
    {
        $clone = clone $this;
        $clone->webBotAuthDomain = self::normalizeNullableString($domain);

        return $clone;
    }

    /**
     * Return a cloned options instance with retry count.
     *
     * @throws InvalidArgumentException When `$retries` is negative.
     */
    public function withRetries(int $retries): static
    {
        if ($retries < 0) {
            throw new InvalidArgumentException('retries must be greater than or equal to 0.');
        }

        $clone = clone $this;
        $clone->retries = $retries;

        return $clone;
    }

    /**
     * Return a cloned options instance with SDK-level execution timeout.
     *
     * @throws InvalidArgumentException When `$seconds` is not null and lower than or equal to zero.
     */
    public function withExecutionTimeoutSeconds(?float $seconds): static
    {
        if ($seconds !== null && $seconds <= 0) {
            throw new InvalidArgumentException('executionTimeoutSeconds must be null or greater than 0.');
        }

        $clone = clone $this;
        $clone->executionTimeoutSeconds = $seconds;

        return $clone;
    }

    /**
     * Check whether TLS host verification is disabled.
     */
    public function insecureDisableTlsHostVerification(): bool
    {
        return $this->insecureDisableTlsHostVerification;
    }

    /**
     * Check whether robots directives are enforced.
     */
    public function obeyRobots(): bool
    {
        return $this->obeyRobots;
    }

    /**
     * Return configured HTTP proxy endpoint.
     */
    public function httpProxy(): ?string
    {
        return $this->httpProxy;
    }

    /**
     * Return configured proxy bearer token.
     */
    public function proxyBearerToken(): ?string
    {
        return $this->proxyBearerToken;
    }

    /**
     * Return max concurrent HTTP requests.
     */
    public function httpMaxConcurrent(): int
    {
        return $this->httpMaxConcurrent;
    }

    /**
     * Return max open HTTP connections per host.
     */
    public function httpMaxHostOpen(): int
    {
        return $this->httpMaxHostOpen;
    }

    /**
     * Return HTTP connect timeout in milliseconds.
     */
    public function httpConnectTimeout(): int
    {
        return $this->httpConnectTimeout;
    }

    /**
     * Return HTTP request timeout in milliseconds.
     */
    public function httpTimeout(): int
    {
        return $this->httpTimeout;
    }

    /**
     * Return HTTP max response size in bytes when configured.
     */
    public function httpMaxResponseSize(): ?int
    {
        return $this->httpMaxResponseSize;
    }

    /**
     * Return configured log level.
     */
    public function logLevel(): LogLevel
    {
        return $this->logLevel;
    }

    /**
     * Return configured log format.
     */
    public function logFormat(): LogFormat
    {
        return $this->logFormat;
    }

    /**
     * @return list<string>
     */
    public function logFilterScopes(): array
    {
        return $this->logFilterScopes;
    }

    /**
     * Return configured user-agent suffix.
     */
    public function userAgentSuffix(): ?string
    {
        return $this->userAgentSuffix;
    }

    /**
     * Return configured WebBot auth key file path.
     */
    public function webBotAuthKeyFile(): ?string
    {
        return $this->webBotAuthKeyFile;
    }

    /**
     * Return configured WebBot auth key id.
     */
    public function webBotAuthKeyId(): ?string
    {
        return $this->webBotAuthKeyId;
    }

    /**
     * Return configured WebBot auth domain.
     */
    public function webBotAuthDomain(): ?string
    {
        return $this->webBotAuthDomain;
    }

    /**
     * Return configured retry count.
     */
    public function retries(): int
    {
        return $this->retries;
    }

    /**
     * Return configured execution timeout in seconds.
     */
    public function executionTimeoutSeconds(): ?float
    {
        return $this->executionTimeoutSeconds;
    }

    /**
     * Normalize nullable string values by trimming and empty-string collapsing.
     */
    private static function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
