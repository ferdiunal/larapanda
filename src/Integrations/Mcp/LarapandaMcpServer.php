<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use Ferdiunal\Larapanda\Integrations\Exceptions\MissingLaravelIntegrationDependencyException;
use InvalidArgumentException;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

/**
 * Helper for registering Lightpanda tools into Laravel MCP routes.
 */
final class LarapandaMcpServer
{
    /**
     * Cache of generated MCP server classes keyed by tool-set hash.
     *
     * @var array<string, class-string>
     */
    private static array $generatedServerClassMap = [];

    /**
     * Register a local stdio MCP server with Lightpanda tool classes.
     *
     * @param  string  $name  MCP server name exposed to client hosts.
     * @param  list<class-string>|null  $toolClasses  Optional custom tool class list.
     *
     * @throws MissingLaravelIntegrationDependencyException When Laravel MCP package is not installed.
     */
    public static function registerLocal(string $name = 'lightpanda', ?array $toolClasses = null): void
    {
        if (! class_exists(Mcp::class)) {
            throw new MissingLaravelIntegrationDependencyException(
                'Laravel MCP integration requires [laravel/mcp]. Install it with: composer require laravel/mcp'
            );
        }

        if ($toolClasses === null) {
            /** @var list<string> $configuredTools */
            $configuredTools = array_values(array_filter((array) config('larapanda.integrations.mcp.exposed_tools', []), 'is_string'));
            $toolClasses = $configuredTools === [] ? LarapandaMcpTools::all() : LarapandaMcpTools::only($configuredTools);
        }

        Mcp::local($name, self::resolveServerClass($toolClasses));
    }

    /**
     * Resolve a generated class-string<\Laravel\Mcp\Server> for a tool class list.
     *
     * @param  list<class-string>  $toolClasses
     * @return class-string
     *
     * @throws MissingLaravelIntegrationDependencyException
     * @throws InvalidArgumentException
     */
    private static function resolveServerClass(array $toolClasses): string
    {
        if (! class_exists(Server::class) || ! class_exists(Tool::class)) {
            throw new MissingLaravelIntegrationDependencyException(
                'Laravel MCP integration requires [laravel/mcp]. Install it with: composer require laravel/mcp'
            );
        }

        $normalizedTools = self::normalizeToolClasses($toolClasses);
        $cacheKey = sha1(implode('|', $normalizedTools));

        if (isset(self::$generatedServerClassMap[$cacheKey])) {
            return self::$generatedServerClassMap[$cacheKey];
        }

        $suffix = strtoupper(substr($cacheKey, 0, 12));
        $shortClass = "GeneratedLarapandaMcpServer{$suffix}";
        $namespace = 'Ferdiunal\\Larapanda\\Integrations\\Mcp\\Generated';
        $fqcn = "{$namespace}\\{$shortClass}";

        if (! class_exists($fqcn, false)) {
            $classBody = self::buildGeneratedServerClassBody($namespace, $shortClass, $normalizedTools);
            eval($classBody);
        }

        if (! class_exists($fqcn, false)) {
            throw new RuntimeException('Failed to generate Larapanda MCP server class.');
        }

        self::$generatedServerClassMap[$cacheKey] = $fqcn;

        return $fqcn;
    }

    /**
     * @param  list<class-string>  $toolClasses
     * @return list<class-string<Tool>>
     */
    private static function normalizeToolClasses(array $toolClasses): array
    {
        if ($toolClasses === []) {
            throw new InvalidArgumentException('At least one MCP tool class must be registered.');
        }

        $normalized = [];
        $seen = [];

        foreach ($toolClasses as $toolClass) {
            if (! class_exists($toolClass)) {
                throw new InvalidArgumentException("MCP tool class [{$toolClass}] does not exist.");
            }

            if (! is_subclass_of($toolClass, Tool::class)) {
                throw new InvalidArgumentException("MCP tool class [{$toolClass}] must extend [Laravel\\Mcp\\Server\\Tool].");
            }

            /** @var class-string<Tool> $toolClass */
            $normalizedToolClass = ltrim($toolClass, '\\');
            /** @var class-string<Tool> $normalizedToolClass */
            if (isset($seen[$normalizedToolClass])) {
                continue;
            }

            $seen[$normalizedToolClass] = true;
            $normalized[] = $normalizedToolClass;
        }

        return $normalized;
    }

    /**
     * @param  list<class-string<Tool>>  $toolClasses
     */
    private static function buildGeneratedServerClassBody(string $namespace, string $shortClass, array $toolClasses): string
    {
        $toolsLiteral = implode(
            ', ',
            array_map(
                static fn (string $toolClass): string => '\\'.ltrim($toolClass, '\\').'::class',
                $toolClasses,
            ),
        );

        return <<<PHP
namespace {$namespace};

final class {$shortClass} extends \\Laravel\\Mcp\\Server
{
    protected array \$tools = [{$toolsLiteral}];
}
PHP;
    }
}
