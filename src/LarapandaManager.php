<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda;

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Contracts\LarapandaClientInterface;
use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;
use Ferdiunal\Larapanda\Exceptions\InvalidInstanceConfigurationException;
use Ferdiunal\Larapanda\Runtime\Command\LightpandaCommandFactory;
use Ferdiunal\Larapanda\Runtime\NativeProcessRunner;
use Ferdiunal\Larapanda\Runtime\ProcessRunner;
use Ferdiunal\Larapanda\Runtime\RuntimeResolver;

/**
 * Manager responsible for instance profile hydration and named client resolution.
 */
final class LarapandaManager implements LarapandaManagerInterface
{
    /** @var array<string, InstanceProfile> */
    private array $profiles = [];

    /** @var array<string, LarapandaClientInterface> */
    private array $instances = [];

    private string $defaultInstanceName = 'default';

    /**
     * @param  array<string, mixed>  $config
     * @param  ProcessRunner|null  $processRunner  Process runner override used for testing or custom execution.
     * @param  RuntimeResolver|null  $runtimeResolver  Runtime resolver override used for testing or custom execution.
     * @param  LightpandaCommandFactory|null  $commandFactory  Command factory override used for testing or customization.
     */
    public function __construct(
        array $config = [],
        private readonly ?ProcessRunner $processRunner = null,
        private readonly ?RuntimeResolver $runtimeResolver = null,
        private readonly ?LightpandaCommandFactory $commandFactory = null,
    ) {
        $this->hydrateFromConfig($config);
    }

    /**
     * Resolve a named client instance.
     *
     * @param  string  $name  Configured instance name.
     *
     * @throws InvalidInstanceConfigurationException When the requested instance does not exist.
     */
    public function instance(string $name = 'default'): LarapandaClientInterface
    {
        $target = $name !== '' ? $name : $this->defaultInstanceName;

        if (! isset($this->profiles[$target])) {
            throw new InvalidInstanceConfigurationException("Unknown Larapanda instance [{$target}].");
        }

        if (! isset($this->instances[$target])) {
            $this->instances[$target] = new LarapandaClient(
                profile: $this->profiles[$target],
                processRunner: $this->processRunner ?? new NativeProcessRunner,
                runtimeResolver: $this->runtimeResolver ?? new RuntimeResolver,
                commandFactory: $this->commandFactory ?? new LightpandaCommandFactory,
            );
        }

        return $this->instances[$target];
    }

    /**
     * Return a normalized immutable profile by instance name.
     *
     * @param  string  $name  Configured instance name.
     *
     * @throws InvalidInstanceConfigurationException When the requested instance does not exist.
     */
    public function profile(string $name = 'default'): InstanceProfile
    {
        $target = $name !== '' ? $name : $this->defaultInstanceName;

        if (! isset($this->profiles[$target])) {
            throw new InvalidInstanceConfigurationException("Unknown Larapanda instance [{$target}].");
        }

        return $this->profiles[$target];
    }

    /**
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidInstanceConfigurationException When the configuration shape is invalid.
     */
    private function hydrateFromConfig(array $config): void
    {
        $defaultInstance = $config['default_instance'] ?? 'default';
        if (! is_string($defaultInstance) || trim($defaultInstance) === '') {
            throw new InvalidInstanceConfigurationException('[default_instance] must be a non-empty string.');
        }

        $this->defaultInstanceName = $defaultInstance;

        $defaults = $config['defaults'] ?? [];
        if (! is_array($defaults)) {
            throw new InvalidInstanceConfigurationException('[defaults] must be an array.');
        }

        $instances = $config['instances'] ?? ['default' => []];
        if (! is_array($instances)) {
            throw new InvalidInstanceConfigurationException('[instances] must be an array.');
        }

        if ($instances === []) {
            throw new InvalidInstanceConfigurationException('At least one instance must be configured.');
        }

        foreach ($instances as $name => $instanceConfig) {
            if (! is_string($name) || trim($name) === '') {
                throw new InvalidInstanceConfigurationException('Instance names must be non-empty strings.');
            }

            if (! is_array($instanceConfig)) {
                throw new InvalidInstanceConfigurationException("Instance [{$name}] config must be an array.");
            }

            /** @var array<string, mixed> $merged */
            $merged = array_replace_recursive($defaults, $instanceConfig);
            $this->profiles[$name] = InstanceProfile::fromArray($name, $merged);
        }

        if (! isset($this->profiles[$this->defaultInstanceName])) {
            throw new InvalidInstanceConfigurationException(
                "Configured default instance [{$this->defaultInstanceName}] is not present in [instances]."
            );
        }
    }
}
