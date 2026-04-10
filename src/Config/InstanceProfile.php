<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Config;

use Ferdiunal\Larapanda\Enums\RuntimeMode;
use Ferdiunal\Larapanda\Exceptions\InvalidInstanceConfigurationException;

/**
 * Immutable normalized runtime profile for a named Lightpanda instance.
 */
final readonly class InstanceProfile
{
    /**
     * @param  array<string, string>  $environment
     * @param  list<string>  $dockerExtraArgs
     */
    public function __construct(
        public string $name,
        public RuntimeMode $runtimeMode,
        public ?string $binaryPath,
        public string $dockerCommand,
        public string $dockerImage,
        public ?string $dockerContainerName,
        public bool $dockerRemove,
        public array $dockerExtraArgs,
        public array $environment,
        public ?string $workingDirectory,
    ) {}

    /**
     * Build a profile from raw configuration values.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidInstanceConfigurationException When the profile contains invalid runtime values.
     */
    public static function fromArray(string $name, array $config): self
    {
        $runtimeRaw = $config['runtime'] ?? RuntimeMode::Auto->value;
        if (! is_string($runtimeRaw)) {
            throw new InvalidInstanceConfigurationException("Invalid runtime mode for instance [{$name}].");
        }

        $runtime = RuntimeMode::tryFrom($runtimeRaw);

        if ($runtime === null) {
            throw new InvalidInstanceConfigurationException("Invalid runtime mode for instance [{$name}].");
        }

        $binaryPath = self::normalizeString($config['binary_path'] ?? null);

        if ($runtime === RuntimeMode::Cli && $binaryPath === null) {
            throw new InvalidInstanceConfigurationException(
                "Instance [{$name}] uses CLI runtime and requires [binary_path]."
            );
        }

        $docker = $config['docker'] ?? [];

        if (! is_array($docker)) {
            throw new InvalidInstanceConfigurationException("Instance [{$name}] docker config must be an array.");
        }

        $dockerImage = self::normalizeString($docker['image'] ?? 'lightpanda/browser:nightly') ?? 'lightpanda/browser:nightly';
        $dockerCommand = self::normalizeString($docker['command'] ?? 'docker') ?? 'docker';
        $dockerContainerName = self::normalizeString($docker['container_name'] ?? null);
        $dockerRemove = (bool) ($docker['remove'] ?? true);
        $dockerExtraArgs = self::normalizeStringList($docker['extra_args'] ?? [], "instances.{$name}.docker.extra_args");
        $environment = self::normalizeStringMap($config['environment'] ?? [], "instances.{$name}.environment");
        $workingDirectory = self::normalizeString($config['working_directory'] ?? null);

        return new self(
            name: $name,
            runtimeMode: $runtime,
            binaryPath: $binaryPath,
            dockerCommand: $dockerCommand,
            dockerImage: $dockerImage,
            dockerContainerName: $dockerContainerName,
            dockerRemove: $dockerRemove,
            dockerExtraArgs: $dockerExtraArgs,
            environment: $environment,
            workingDirectory: $workingDirectory,
        );
    }

    /**
     * Normalize nullable string configuration values.
     */
    private static function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Normalize a list of non-empty string values.
     *
     * @return list<string>
     *
     * @throws InvalidInstanceConfigurationException When the value is not a string list.
     */
    private static function normalizeStringList(mixed $value, string $key): array
    {
        if (! is_array($value)) {
            throw new InvalidInstanceConfigurationException("{$key} must be an array.");
        }

        $normalized = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new InvalidInstanceConfigurationException("{$key} must contain only strings.");
            }

            $trimmed = trim($item);

            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return $normalized;
    }

    /**
     * Normalize a key/value map of environment strings.
     *
     * @return array<string, string>
     *
     * @throws InvalidInstanceConfigurationException When the value is not a string map.
     */
    private static function normalizeStringMap(mixed $value, string $key): array
    {
        if (! is_array($value)) {
            throw new InvalidInstanceConfigurationException("{$key} must be an array.");
        }

        $normalized = [];

        foreach ($value as $mapKey => $mapValue) {
            if (! is_string($mapKey) || ! is_string($mapValue)) {
                throw new InvalidInstanceConfigurationException("{$key} must be a key/value string map.");
            }

            $normalized[$mapKey] = $mapValue;
        }

        return $normalized;
    }
}
