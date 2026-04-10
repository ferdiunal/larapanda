<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime;

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Enums\RuntimeMode;
use Ferdiunal\Larapanda\Exceptions\InvalidInstanceConfigurationException;
use Ferdiunal\Larapanda\Exceptions\RuntimeUnavailableException;

/**
 * Resolves the effective runtime backend for an instance profile.
 */
final class RuntimeResolver
{
    /** @var callable(string): bool */
    private mixed $isCommandAvailable;

    /** @var callable(string): bool */
    private mixed $isBinaryExecutable;

    /**
     * @param  callable(string): bool|null  $isCommandAvailable
     * @param  callable(string): bool|null  $isBinaryExecutable
     */
    public function __construct(?callable $isCommandAvailable = null, ?callable $isBinaryExecutable = null)
    {
        $this->isCommandAvailable = $isCommandAvailable ?? static fn (string $command): bool => self::commandExists($command);
        $this->isBinaryExecutable = $isBinaryExecutable ?? static fn (string $path): bool => self::binaryExecutable($path);
    }

    /**
     * Resolve the effective runtime mode for the given profile.
     *
     * @throws InvalidInstanceConfigurationException When profile data is inconsistent.
     * @throws RuntimeUnavailableException When required runtime dependencies are missing.
     */
    public function resolve(InstanceProfile $profile): RuntimeMode
    {
        return match ($profile->runtimeMode) {
            RuntimeMode::Cli => $this->resolveCli($profile),
            RuntimeMode::Docker => $this->resolveDocker($profile),
            RuntimeMode::Auto => $this->resolveAuto($profile),
        };
    }

    /**
     * Resolve strict CLI mode requirements.
     *
     * @throws InvalidInstanceConfigurationException
     * @throws RuntimeUnavailableException
     */
    private function resolveCli(InstanceProfile $profile): RuntimeMode
    {
        if ($profile->binaryPath === null) {
            throw new InvalidInstanceConfigurationException(
                "Instance [{$profile->name}] requires [binary_path] when runtime is [cli]."
            );
        }

        if (! ($this->isBinaryExecutable)($profile->binaryPath)) {
            throw new RuntimeUnavailableException(
                "CLI runtime selected but Lightpanda binary is not executable: {$profile->binaryPath}"
            );
        }

        return RuntimeMode::Cli;
    }

    /**
     * Resolve strict Docker mode requirements.
     *
     * @throws RuntimeUnavailableException
     */
    private function resolveDocker(InstanceProfile $profile): RuntimeMode
    {
        if (! ($this->isCommandAvailable)($profile->dockerCommand)) {
            throw new RuntimeUnavailableException(
                "Docker runtime selected but docker command is not available: {$profile->dockerCommand}"
            );
        }

        return RuntimeMode::Docker;
    }

    /**
     * Resolve auto mode by preferring valid CLI configuration, then Docker fallback.
     *
     * @throws InvalidInstanceConfigurationException
     * @throws RuntimeUnavailableException
     */
    private function resolveAuto(InstanceProfile $profile): RuntimeMode
    {
        if ($profile->binaryPath !== null) {
            if (! ($this->isBinaryExecutable)($profile->binaryPath)) {
                throw new InvalidInstanceConfigurationException(
                    "Instance [{$profile->name}] provided [binary_path] but file is not executable: {$profile->binaryPath}"
                );
            }

            return RuntimeMode::Cli;
        }

        if (($this->isCommandAvailable)($profile->dockerCommand)) {
            return RuntimeMode::Docker;
        }

        throw new RuntimeUnavailableException(
            sprintf(
                'No runtime available for instance [%s]. Set [binary_path] for CLI or install Docker.',
                $profile->name
            )
        );
    }

    /**
     * Check whether a command name resolves to an executable binary.
     */
    private static function commandExists(string $command): bool
    {
        return self::binaryExecutable($command);
    }

    /**
     * Check whether a binary path or command is executable in the current environment.
     */
    private static function binaryExecutable(string $binary): bool
    {
        $candidate = trim($binary);

        if ($candidate === '') {
            return false;
        }

        if (str_contains($candidate, DIRECTORY_SEPARATOR)) {
            return is_file($candidate) && is_executable($candidate);
        }

        $path = getenv('PATH');
        if (! is_string($path) || trim($path) === '') {
            return false;
        }

        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            $fullPath = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$candidate;
            if (is_file($fullPath) && is_executable($fullPath)) {
                return true;
            }
        }

        return false;
    }
}
