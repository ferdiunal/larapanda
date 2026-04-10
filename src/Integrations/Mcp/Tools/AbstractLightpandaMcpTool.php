<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp\Tools;

use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolCatalog;
use Ferdiunal\Larapanda\Integrations\Mcp\McpLightpandaToolInvoker;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Schema;
use Laravel\Mcp\Server\Tool;

/**
 * Base MCP server tool that proxies execution to Lightpanda over stdio bridge.
 */
abstract class AbstractLightpandaMcpTool extends Tool
{
    public function __construct(
        private readonly LightpandaToolCatalog $catalog,
        private readonly McpLightpandaToolInvoker $invoker,
    ) {}

    /**
     * Return canonical Lightpanda MCP tool name.
     */
    abstract protected function canonicalToolName(): string;

    /**
     * Resolve tool description from catalog.
     */
    public function description(): string
    {
        return $this->catalog->byName($this->canonicalToolName())->description;
    }

    /**
     * Build MCP schema from catalog metadata.
     */
    /**
     * @return array<string, mixed>
     */
    public function schema(Schema $schema): array
    {
        $definition = $this->catalog->byName($this->canonicalToolName());
        $properties = [];

        foreach ($definition->properties as $name => $property) {
            $type = isset($property['type']) && is_string($property['type']) ? $property['type'] : 'string';
            $description = isset($property['description']) && is_string($property['description']) ? $property['description'] : '';
            $properties[$name] = $this->buildSchemaProperty($schema, $type, $description);
        }

        return $schema->object(
            properties: $properties,
            required: $definition->required,
        );
    }

    /**
     * Invoke Lightpanda tool and return MCP text response.
     */
    public function handle(Request $request): Response
    {
        $payload = $this->extractPayload($request);
        try {
            $result = $this->invoker->invoke($this->canonicalToolName(), $payload);
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        return Response::text(json_encode($result, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayload(Request $request): array
    {
        return $request->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchemaProperty(Schema $schema, string $type, string $description): array
    {
        return match ($type) {
            'integer' => $schema->integer(description: $description),
            'boolean' => $schema->boolean(description: $description),
            'number' => $schema->number(description: $description),
            default => $schema->string(description: $description),
        };
    }
}
