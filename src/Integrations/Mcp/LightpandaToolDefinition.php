<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

/**
 * Immutable metadata and schema definition for one Lightpanda MCP tool.
 */
final readonly class LightpandaToolDefinition
{
    /**
     * @param  string  $name  Canonical tool name exposed by Lightpanda MCP.
     * @param  string  $description  Operational tool description.
     * @param  array<string, array<string, mixed>>  $properties  JSON-schema-like property map.
     * @param  list<string>  $required  Required property names.
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $properties,
        public array $required = [],
    ) {}
}
