<?php

declare(strict_types=1);

use Illuminate\Support\Env;

/**
 * Resolve integer env values with strict fallback behavior.
 *
 * @var Closure(string, int): int $intEnv
 */
$intEnv = static function (string $key, int $default): int {
    $value = Env::get($key);

    if ($value === null || ! is_numeric($value)) {
        return $default;
    }

    return (int) $value;
};

/**
 * Resolve floating-point env values with strict fallback behavior.
 *
 * @var Closure(string, float): float $floatEnv
 */
$floatEnv = static function (string $key, float $default): float {
    $value = Env::get($key);

    if ($value === null || ! is_numeric($value)) {
        return $default;
    }

    return (float) $value;
};

/**
 * Resolve boolean env values with strict fallback behavior.
 *
 * @var Closure(string, bool): bool $boolEnv
 */
$boolEnv = static function (string $key, bool $default): bool {
    $value = Env::get($key);

    if ($value === null) {
        return $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    if (! is_string($value)) {
        return $default;
    }

    $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    return $normalized ?? $default;
};

/**
 * @var array<string, mixed>
 */
return [
    /**
     * Default instance key used when no explicit profile name is provided.
     */
    'default_instance' => Env::get('LARAPANDA_DEFAULT_INSTANCE', 'default'),

    /**
     * Base profile values inherited by every named instance via deep merge.
     */
    'defaults' => [
        /**
         * Runtime mode selector: `auto`, `cli`, or `docker`.
         */
        'runtime' => Env::get('LARAPANDA_RUNTIME', 'cli'),

        /**
         * Absolute path to the Lightpanda CLI binary. Required for strict `cli` mode.
         */
        'binary_path' => Env::get('LARAPANDA_BINARY_PATH'),

        /**
         * Optional process working directory used by process runner.
         */
        'working_directory' => Env::get('LARAPANDA_WORKING_DIRECTORY'),

        /**
         * Environment variables injected into Lightpanda process execution.
         *
         * @var array<string, string>
         */
        'environment' => [
            'LIGHTPANDA_DISABLE_TELEMETRY' => Env::get('LARAPANDA_DISABLE_TELEMETRY', true) ? 'true' : 'false',
        ],

        /**
         * Docker launcher defaults used when runtime resolves to Docker.
         */
        'docker' => [
            /** Docker executable or command alias. */
            'command' => Env::get('LARAPANDA_DOCKER_COMMAND', 'docker'),

            /** Docker image containing Lightpanda runtime. */
            'image' => Env::get('LARAPANDA_DOCKER_IMAGE', 'lightpanda/browser:nightly'),

            /** Optional base container name used for generated runtime containers. */
            'container_name' => Env::get('LARAPANDA_DOCKER_CONTAINER_NAME', 'larapanda-lightpanda'),

            /** Whether the container should be automatically removed on exit. */
            'remove' => true,

            /**
             * Extra raw Docker arguments appended before image name.
             *
             * @var list<string>
             */
            'extra_args' => [],
        ],
    ],

    /**
     * Named runtime profiles.
     *
     * Each profile inherits from `defaults` and can override any supported field.
     */
    'instances' => [
        /** Primary default profile used by the package. */
        'default' => [],

        /**
         * Example crawler profile placeholder.
         *
         * Configure strict CLI mode by setting `runtime=cli` and `binary_path`.
         */
        'crawler' => [
            // Example for strict CLI usage:
            // 'runtime' => 'cli',
            // 'binary_path' => '/absolute/path/to/lightpanda',
        ],

        /**
         * Example MCP profile placeholder.
         *
         * Configure strict Docker mode by setting `runtime=docker`.
         */
        'mcp' => [
            // Example for explicit Docker usage:
            // 'runtime' => 'docker',
        ],
    ],

    /**
     * Optional Laravel AI SDK and MCP server integration settings.
     */
    'integrations' => [
        /**
         * AI SDK tool adapter configuration.
         */
        'ai' => [
            /**
             * Instance profile used when AI tools invoke Lightpanda MCP commands.
             */
            'instance' => Env::get('LARAPANDA_AI_INSTANCE', 'default'),

            /**
             * Prefix used for exported AI tool names.
             */
            'tool_prefix' => Env::get('LARAPANDA_AI_TOOL_PREFIX', 'lightpanda_'),

            /**
             * List of canonical Lightpanda tool names to expose.
             *
             * Empty list means all catalog tools are exposed.
             *
             * @var list<string>
             */
            'exposed_tools' => [],

            /**
             * Session inactivity TTL in seconds.
             */
            'session_ttl_seconds' => $intEnv('LARAPANDA_AI_SESSION_TTL', 300),

            /**
             * Maximum number of concurrent in-memory sessions.
             */
            'max_sessions' => $intEnv('LARAPANDA_AI_MAX_SESSIONS', 16),

            /**
             * Default session key used when caller does not provide one.
             */
            'default_session_id' => Env::get('LARAPANDA_AI_DEFAULT_SESSION_ID', 'default'),

            /**
             * Timeout for one MCP request/response roundtrip.
             */
            'request_timeout_seconds' => $floatEnv('LARAPANDA_AI_REQUEST_TIMEOUT', 15.0),

            /**
             * Whether MCP navigation should obey robots.txt where applicable.
             */
            'obey_robots' => $boolEnv('LARAPANDA_AI_OBEY_ROBOTS', true),

            /**
             * Optional HTTP proxy for all MCP network requests.
             */
            'http_proxy' => Env::get('LARAPANDA_AI_HTTP_PROXY'),

            /**
             * Optional bearer token for proxy authorization.
             */
            'proxy_bearer_token' => Env::get('LARAPANDA_AI_PROXY_BEARER_TOKEN'),
        ],

        /**
         * Laravel MCP server adapter configuration.
         */
        'mcp' => [
            /**
             * Instance profile used by Laravel MCP tools when proxying to Lightpanda.
             */
            'instance' => Env::get('LARAPANDA_MCP_INTEGRATION_INSTANCE', 'mcp'),

            /**
             * List of canonical Lightpanda tool names to expose.
             *
             * Empty list means all catalog tools are exposed.
             *
             * @var list<string>
             */
            'exposed_tools' => [],

            /**
             * Session inactivity TTL in seconds.
             */
            'session_ttl_seconds' => $intEnv('LARAPANDA_MCP_SESSION_TTL', 300),

            /**
             * Maximum number of concurrent in-memory sessions.
             */
            'max_sessions' => $intEnv('LARAPANDA_MCP_MAX_SESSIONS', 32),

            /**
             * Default session key used when caller does not provide one.
             */
            'default_session_id' => Env::get('LARAPANDA_MCP_DEFAULT_SESSION_ID', 'default'),

            /**
             * Timeout for one MCP request/response roundtrip.
             */
            'request_timeout_seconds' => $floatEnv('LARAPANDA_MCP_REQUEST_TIMEOUT', 15.0),

            /**
             * Whether MCP navigation should obey robots.txt where applicable.
             */
            'obey_robots' => $boolEnv('LARAPANDA_MCP_OBEY_ROBOTS', true),

            /**
             * Optional HTTP proxy for all MCP network requests.
             */
            'http_proxy' => Env::get('LARAPANDA_MCP_HTTP_PROXY'),

            /**
             * Optional bearer token for proxy authorization.
             */
            'proxy_bearer_token' => Env::get('LARAPANDA_MCP_PROXY_BEARER_TOKEN'),
        ],
    ],
];
