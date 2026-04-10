<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Options;

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Enums\LogFormat;
use Ferdiunal\Larapanda\Enums\LogLevel;
use Ferdiunal\Larapanda\Enums\StripMode;
use Ferdiunal\Larapanda\Enums\WaitUntil;
use InvalidArgumentException;

/**
 * Immutable method-scoped options for `lightpanda fetch`.
 */
final class FetchOptions extends AbstractLightpandaOptions
{
    /** Optional fetch output dump format. */
    private ?FetchDumpFormat $dump = null;

    /** @var list<StripMode> */
    private array $stripModes = [];

    /** Include computed base URLs in output. */
    private bool $withBase = false;

    /** Include frame content in output. */
    private bool $withFrames = false;

    /** Wait duration in milliseconds before extraction. */
    private int $waitMs = 5000;

    /** Page readiness checkpoint used by fetch. */
    private WaitUntil $waitUntil = WaitUntil::Done;

    /**
     * Return a cloned fetch options instance with common and fetch-specific settings applied.
     *
     * Null values are ignored and do not mutate current state.
     *
     * @param  list<string>|null  $logFilterScopes
     * @param  list<StripMode>|null  $stripModes
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
        ?FetchDumpFormat $dump = null,
        ?array $stripModes = null,
        ?bool $withBase = null,
        ?bool $withFrames = null,
        ?int $waitMs = null,
        ?WaitUntil $waitUntil = null,
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

        if ($dump !== null) {
            $options = $options->withDump($dump);
        }

        if ($stripModes !== null) {
            $options = $options->withStripModes($stripModes);
        }

        if ($withBase !== null) {
            $options = $options->withBase($withBase);
        }

        if ($withFrames !== null) {
            $options = $options->withFrames($withFrames);
        }

        if ($waitMs !== null) {
            $options = $options->withWaitMs($waitMs);
        }

        if ($waitUntil !== null) {
            $options = $options->withWaitUntil($waitUntil);
        }

        return $options;
    }

    /**
     * Return a cloned fetch options instance with dump format.
     */
    public function withDump(?FetchDumpFormat $dump): self
    {
        $clone = clone $this;
        $clone->dump = $dump;

        return $clone;
    }

    /**
     * Return a cloned fetch options instance with normalized strip modes.
     *
     * @param  list<StripMode>  $modes
     */
    public function withStripModes(array $modes): self
    {
        $clone = clone $this;
        $clone->stripModes = array_values(array_unique($modes, SORT_REGULAR));

        return $clone;
    }

    /**
     * Return a cloned fetch options instance with one extra strip mode.
     */
    public function withStripMode(StripMode $mode): self
    {
        return $this->withStripModes([...$this->stripModes, $mode]);
    }

    /**
     * Return a cloned fetch options instance with base URL inclusion toggled.
     */
    public function withBase(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->withBase = $enabled;

        return $clone;
    }

    /**
     * Return a cloned fetch options instance with frame inclusion toggled.
     */
    public function withFrames(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->withFrames = $enabled;

        return $clone;
    }

    /**
     * Return a cloned fetch options instance with wait duration.
     *
     * @throws InvalidArgumentException When `$milliseconds` is negative.
     */
    public function withWaitMs(int $milliseconds): self
    {
        if ($milliseconds < 0) {
            throw new InvalidArgumentException('waitMs must be greater than or equal to 0.');
        }

        $clone = clone $this;
        $clone->waitMs = $milliseconds;

        return $clone;
    }

    /**
     * Return a cloned fetch options instance with wait checkpoint.
     */
    public function withWaitUntil(WaitUntil $waitUntil): self
    {
        $clone = clone $this;
        $clone->waitUntil = $waitUntil;

        return $clone;
    }

    /**
     * Return configured dump format.
     */
    public function dump(): ?FetchDumpFormat
    {
        return $this->dump;
    }

    /**
     * @return list<StripMode>
     */
    public function stripModes(): array
    {
        return $this->stripModes;
    }

    /**
     * Check whether base URL details are enabled.
     */
    public function withBaseEnabled(): bool
    {
        return $this->withBase;
    }

    /**
     * Check whether frame extraction is enabled.
     */
    public function withFramesEnabled(): bool
    {
        return $this->withFrames;
    }

    /**
     * Return wait duration in milliseconds.
     */
    public function waitMs(): int
    {
        return $this->waitMs;
    }

    /**
     * Return wait readiness checkpoint.
     */
    public function waitUntil(): WaitUntil
    {
        return $this->waitUntil;
    }
}
