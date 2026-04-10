<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda;

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Contracts\LarapandaClientInterface;
use Ferdiunal\Larapanda\Exceptions\ProcessExecutionException;
use Ferdiunal\Larapanda\Options\AbstractLightpandaOptions;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;
use Ferdiunal\Larapanda\Requests\PendingFetchRequest;
use Ferdiunal\Larapanda\Requests\PendingMcpRequest;
use Ferdiunal\Larapanda\Requests\PendingServeRequest;
use Ferdiunal\Larapanda\Runtime\Command\LightpandaCommandFactory;
use Ferdiunal\Larapanda\Runtime\FetchResult;
use Ferdiunal\Larapanda\Runtime\ProcessResult;
use Ferdiunal\Larapanda\Runtime\ProcessRunner;
use Ferdiunal\Larapanda\Runtime\RunningInstanceHandle;
use Ferdiunal\Larapanda\Runtime\RuntimeResolver;
use InvalidArgumentException;
use Throwable;

/**
 * Concrete client that executes Lightpanda commands for one immutable instance profile.
 */
final class LarapandaClient implements LarapandaClientInterface
{
    /**
     * @param  InstanceProfile  $profile  Immutable instance configuration for runtime and process settings.
     * @param  ProcessRunner  $processRunner  Process abstraction responsible for command execution.
     * @param  RuntimeResolver  $runtimeResolver  Runtime selector for auto/cli/docker behavior.
     * @param  LightpandaCommandFactory  $commandFactory  Type-safe command argument builder.
     */
    public function __construct(
        private readonly InstanceProfile $profile,
        private readonly ProcessRunner $processRunner,
        private readonly RuntimeResolver $runtimeResolver,
        private readonly LightpandaCommandFactory $commandFactory,
    ) {}

    /**
     * Return the immutable instance profile attached to this client.
     */
    public function profile(): InstanceProfile
    {
        return $this->profile;
    }

    /**
     * Execute a `lightpanda fetch` command and return parsed plus raw process output.
     *
     * @param  string  $url  Absolute URL target.
     * @param  FetchOptions|null  $options  Optional method-scoped fetch options.
     *
     * @throws InvalidArgumentException When the URL is not a valid absolute URL.
     * @throws ProcessExecutionException When process execution fails or critical fetch error markers are detected.
     */
    public function fetch(string $url, ?FetchOptions $options = null): FetchResult
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('fetch URL must be a valid absolute URL.');
        }

        $options ??= new FetchOptions;
        $runtime = $this->runtimeResolver->resolve($this->profile);
        $command = $this->commandFactory->buildFetchCommand($this->profile, $runtime, $options, $url);
        $result = $this->runWithRetries($command, $options);

        if (! $result->isSuccessful()) {
            throw ProcessExecutionException::fromResult($result);
        }

        if ($this->hasCriticalFetchError($result->stderr(), $options->obeyRobots())) {
            throw ProcessExecutionException::fromResult($result);
        }

        return new FetchResult($result, $result->stdout(), $options->dump());
    }

    /**
     * Start a fluent fetch request builder.
     *
     * @param  string  $url  Absolute URL target.
     */
    public function fetchRequest(string $url): PendingFetchRequest
    {
        return new PendingFetchRequest($this, $url);
    }

    /**
     * Start a long-running `lightpanda serve` process.
     *
     * @param  ServeOptions|null  $options  Optional method-scoped serve options.
     * @return RunningInstanceHandle Handle used for lifecycle and output operations.
     *
     * @throws ProcessExecutionException When the process cannot be started after retries.
     */
    public function serve(?ServeOptions $options = null): RunningInstanceHandle
    {
        $options ??= new ServeOptions;
        $runtime = $this->runtimeResolver->resolve($this->profile);
        $command = $this->commandFactory->buildServeCommand($this->profile, $runtime, $options);

        return $this->startWithRetries($command, $options);
    }

    /**
     * Start a fluent serve request builder.
     */
    public function serveRequest(): PendingServeRequest
    {
        return new PendingServeRequest($this);
    }

    /**
     * Start a long-running `lightpanda mcp` process.
     *
     * @param  McpOptions|null  $options  Optional method-scoped MCP options.
     * @return RunningInstanceHandle Handle used for lifecycle and output operations.
     *
     * @throws ProcessExecutionException When the process cannot be started after retries.
     */
    public function mcp(?McpOptions $options = null): RunningInstanceHandle
    {
        $options ??= new McpOptions;
        $runtime = $this->runtimeResolver->resolve($this->profile);
        $command = $this->commandFactory->buildMcpCommand($this->profile, $runtime, $options);

        return $this->startWithRetries($command, $options);
    }

    /**
     * Start a fluent MCP request builder.
     */
    public function mcpRequest(): PendingMcpRequest
    {
        return new PendingMcpRequest($this);
    }

    /**
     * Execute a short-lived command with bounded retry semantics.
     *
     * @param  list<string>  $command
     * @param  AbstractLightpandaOptions  $options  Shared execution options.
     * @return ProcessResult Final process result from the successful or last attempt.
     */
    private function runWithRetries(array $command, AbstractLightpandaOptions $options): ProcessResult
    {
        $attempts = $options->retries() + 1;
        $lastResult = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $result = $this->processRunner->run(
                command: $command,
                timeoutSeconds: $options->executionTimeoutSeconds(),
                environment: $this->profile->environment,
                workingDirectory: $this->profile->workingDirectory,
            );

            $lastResult = $result;

            if ($result->isSuccessful()) {
                return $result;
            }

            if ($attempt < $attempts) {
                usleep(100_000);
            }
        }

        return $lastResult ?? new ProcessResult($command, $command, 1, '', 'Command did not execute.', 0.0);
    }

    /**
     * Start a long-running command with bounded retry semantics.
     *
     * @param  list<string>  $command
     * @param  AbstractLightpandaOptions  $options  Shared execution options.
     * @return RunningInstanceHandle Running handle from the first successful attempt.
     *
     * @throws ProcessExecutionException When startup keeps failing after retries.
     */
    private function startWithRetries(array $command, AbstractLightpandaOptions $options): RunningInstanceHandle
    {
        $attempts = $options->retries() + 1;
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->processRunner->start(
                    command: $command,
                    timeoutSeconds: $options->executionTimeoutSeconds(),
                    environment: $this->profile->environment,
                    workingDirectory: $this->profile->workingDirectory,
                );
            } catch (Throwable $exception) {
                $lastException = $exception;

                if ($attempt < $attempts) {
                    usleep(100_000);
                }
            }
        }

        throw ProcessExecutionException::fromStartFailure($command, $lastException ?? new InvalidArgumentException('Unknown failure.'));
    }

    /**
     * Detect known critical error signatures that can be emitted even with `exitCode=0`.
     *
     * @param  string  $stderr  Raw process standard error stream.
     * @param  bool  $obeyRobots  Whether robots compliance is explicitly enabled for the request.
     */
    private function hasCriticalFetchError(string $stderr, bool $obeyRobots): bool
    {
        if (trim($stderr) === '') {
            return false;
        }

        $lines = preg_split('/\R/', $stderr) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $isFramePeerVerificationNavigateError = preg_match('/\blevel=error\b/i', $trimmed) === 1
                && preg_match('/\bnavigate failed\b/i', $trimmed) === 1
                && preg_match('/\btype=frame\b/i', $trimmed) === 1
                && preg_match('/\berr=PeerFailedVerification\b/i', $trimmed) === 1;
            if ($isFramePeerVerificationNavigateError) {
                continue;
            }

            $isRobotsBlockedError = preg_match('/\blevel=error\b/i', $trimmed) === 1
                && preg_match('/\berr=RobotsBlocked\b/i', $trimmed) === 1
                && preg_match('/\bnavigate failed\b/i', $trimmed) !== 1;
            if ($obeyRobots && $isRobotsBlockedError) {
                continue;
            }

            if (preg_match('/\blevel=error\b/i', $trimmed) === 1) {
                return true;
            }

            if (preg_match('/\bnavigate failed\b/i', $trimmed) === 1) {
                return true;
            }

            $hasConnectionMarker = preg_match('/\berr=(CouldntResolveHost|CouldntConnect|ConnectionFailed)\b/i', $trimmed) === 1;
            if ($hasConnectionMarker) {
                return true;
            }

            $hasTimeoutMarker = preg_match('/\berr=OperationTimedout\b/i', $trimmed) === 1;
            if ($hasTimeoutMarker) {
                $isWarningLevel = preg_match('/\blevel=warn\b/i', $trimmed) === 1;
                if ($isWarningLevel) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }
}
