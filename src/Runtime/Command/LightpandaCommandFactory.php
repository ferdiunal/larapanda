<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime\Command;

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Enums\RuntimeMode;
use Ferdiunal\Larapanda\Exceptions\InvalidInstanceConfigurationException;
use Ferdiunal\Larapanda\Options\AbstractLightpandaOptions;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;

/**
 * Builds argv-safe Lightpanda command arrays for fetch, serve, and mcp operations.
 */
final class LightpandaCommandFactory
{
    /**
     * Build a command for the `fetch` operation.
     *
     * @return list<string>
     */
    public function buildFetchCommand(
        InstanceProfile $profile,
        RuntimeMode $runtime,
        FetchOptions $options,
        string $url,
    ): array {
        $lightpandaArgs = ['fetch'];

        if ($options->dump() !== null) {
            $lightpandaArgs[] = '--dump';
            $lightpandaArgs[] = $options->dump()->value;
        }

        if ($options->stripModes() !== []) {
            $lightpandaArgs[] = '--strip-mode';
            $lightpandaArgs[] = implode(',', array_map(static fn ($mode): string => $mode->value, $options->stripModes()));
        }

        if ($options->withBaseEnabled()) {
            $lightpandaArgs[] = '--with-base';
        }

        if ($options->withFramesEnabled()) {
            $lightpandaArgs[] = '--with-frames';
        }

        $lightpandaArgs[] = '--wait-ms';
        $lightpandaArgs[] = (string) $options->waitMs();
        $lightpandaArgs[] = '--wait-until';
        $lightpandaArgs[] = $options->waitUntil()->value;

        $lightpandaArgs = $this->appendCommonOptions($lightpandaArgs, $options);
        $lightpandaArgs[] = $url;

        return $this->wrapWithRuntime($profile, $runtime, $lightpandaArgs);
    }

    /**
     * Build a command for the `serve` operation.
     *
     * @return list<string>
     */
    public function buildServeCommand(
        InstanceProfile $profile,
        RuntimeMode $runtime,
        ServeOptions $options,
    ): array {
        $lightpandaArgs = [
            'serve',
            '--host',
            $options->host(),
            '--port',
            (string) $options->port(),
            '--timeout',
            (string) $options->timeoutSeconds(),
            '--cdp-max-connections',
            (string) $options->cdpMaxConnections(),
            '--cdp-max-pending-connections',
            (string) $options->cdpMaxPendingConnections(),
        ];

        if ($options->advertiseHost() !== null) {
            $lightpandaArgs[] = '--advertise-host';
            $lightpandaArgs[] = $options->advertiseHost();
        }

        $lightpandaArgs = $this->appendCommonOptions($lightpandaArgs, $options);

        $publishPorts = ["{$options->host()}:{$options->port()}:{$options->port()}"];

        return $this->wrapWithRuntime($profile, $runtime, $lightpandaArgs, $publishPorts, 'serve');
    }

    /**
     * Build a command for the `mcp` operation.
     *
     * @return list<string>
     */
    public function buildMcpCommand(
        InstanceProfile $profile,
        RuntimeMode $runtime,
        McpOptions $options,
    ): array {
        $lightpandaArgs = ['mcp'];
        $lightpandaArgs = $this->appendCommonOptions($lightpandaArgs, $options);

        return $this->wrapWithRuntime($profile, $runtime, $lightpandaArgs, [], 'mcp');
    }

    /**
     * Append options shared by all Lightpanda operations.
     *
     * @param  list<string>  $args
     * @return list<string>
     */
    private function appendCommonOptions(array $args, AbstractLightpandaOptions $options): array
    {
        if ($options->insecureDisableTlsHostVerification()) {
            $args[] = '--insecure-disable-tls-host-verification';
        }

        if ($options->obeyRobots()) {
            $args[] = '--obey-robots';
        }

        if ($options->httpProxy() !== null) {
            $args[] = '--http-proxy';
            $args[] = $options->httpProxy();
        }

        if ($options->proxyBearerToken() !== null) {
            $args[] = '--proxy-bearer-token';
            $args[] = $options->proxyBearerToken();
        }

        $args[] = '--http-max-concurrent';
        $args[] = (string) $options->httpMaxConcurrent();
        $args[] = '--http-max-host-open';
        $args[] = (string) $options->httpMaxHostOpen();
        $args[] = '--http-connect-timeout';
        $args[] = (string) $options->httpConnectTimeout();
        $args[] = '--http-timeout';
        $args[] = (string) $options->httpTimeout();

        if ($options->httpMaxResponseSize() !== null) {
            $args[] = '--http-max-response-size';
            $args[] = (string) $options->httpMaxResponseSize();
        }

        $args[] = '--log-level';
        $args[] = $options->logLevel()->value;
        $args[] = '--log-format';
        $args[] = $options->logFormat()->value;

        if ($options->logFilterScopes() !== []) {
            $args[] = '--log-filter-scopes';
            $args[] = implode(',', $options->logFilterScopes());
        }

        if ($options->userAgentSuffix() !== null) {
            $args[] = '--user-agent-suffix';
            $args[] = $options->userAgentSuffix();
        }

        if ($options->webBotAuthKeyFile() !== null) {
            $args[] = '--web-bot-auth-key-file';
            $args[] = $options->webBotAuthKeyFile();
        }

        if ($options->webBotAuthKeyId() !== null) {
            $args[] = '--web-bot-auth-keyid';
            $args[] = $options->webBotAuthKeyId();
        }

        if ($options->webBotAuthDomain() !== null) {
            $args[] = '--web-bot-auth-domain';
            $args[] = $options->webBotAuthDomain();
        }

        return $args;
    }

    /**
     * Wrap command arguments with the selected runtime launcher.
     *
     * @param  list<string>  $lightpandaArgs
     * @param  list<string>  $publishPorts
     * @return list<string>
     *
     * @throws InvalidInstanceConfigurationException When CLI runtime is selected without binary path.
     */
    private function wrapWithRuntime(
        InstanceProfile $profile,
        RuntimeMode $runtime,
        array $lightpandaArgs,
        array $publishPorts = [],
        string $purpose = 'cmd',
    ): array {
        if ($runtime === RuntimeMode::Cli) {
            $binaryPath = $profile->binaryPath;

            if ($binaryPath === null) {
                throw new InvalidInstanceConfigurationException(
                    "Instance [{$profile->name}] resolved to CLI runtime but binary_path is missing."
                );
            }

            return [$binaryPath, ...$lightpandaArgs];
        }

        $containerName = $profile->dockerContainerName;
        if ($containerName !== null) {
            $containerName .= '-'.$purpose.'-'.bin2hex(random_bytes(3));
        }

        $dockerArgs = [$profile->dockerCommand, 'run'];

        if ($profile->dockerRemove) {
            $dockerArgs[] = '--rm';
        }

        if ($containerName !== null) {
            $dockerArgs[] = '--name';
            $dockerArgs[] = $containerName;
        }

        foreach ($publishPorts as $publishPort) {
            $dockerArgs[] = '-p';
            $dockerArgs[] = $publishPort;
        }

        foreach ($profile->dockerExtraArgs as $extraArg) {
            $dockerArgs[] = $extraArg;
        }

        $dockerArgs[] = $profile->dockerImage;

        return [...$dockerArgs, ...$lightpandaArgs];
    }
}
