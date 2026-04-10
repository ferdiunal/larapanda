<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Ai;

use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolDefinition;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\JsonSchema;
use Laravel\Ai\Tools\Request;

/**
 * AI SDK tool adapter that proxies execution to the Lightpanda MCP bridge.
 */
final readonly class LightpandaAiTool implements Tool
{
    /**
     * @param  string  $namePrefix  Prefix added to canonical tool names (e.g. `lightpanda_`).
     * @param  LightpandaToolDefinition  $definition  Canonical tool metadata.
     * @param  AiLightpandaToolInvoker  $invoker  AI-scoped MCP-backed tool execution service.
     */
    public function __construct(
        private string $namePrefix,
        private LightpandaToolDefinition $definition,
        private AiLightpandaToolInvoker $invoker,
    ) {}

    /**
     * Return public AI tool name with configured prefix.
     */
    public function name(): string
    {
        return $this->namePrefix.$this->definition->name;
    }

    /**
     * Return technical tool description used by model planning.
     */
    public function description(): string
    {
        return $this->definition->description;
    }

    /**
     * Build AI SDK input schema from catalog metadata.
     */
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $properties = [];

        foreach ($this->definition->properties as $name => $property) {
            $type = isset($property['type']) && is_string($property['type']) ? $property['type'] : 'string';
            $description = isset($property['description']) && is_string($property['description']) ? $property['description'] : '';
            $properties[$name] = $this->buildSchemaProperty($schema, $type, $description);
        }

        return $schema->object(
            properties: $properties,
            required: $this->definition->required,
        );
    }

    /**
     * Execute tool call and return JSON text payload for model consumption.
     */
    public function handle(Request $request): string
    {
        $payload = AiToolRequestPayload::fromRequest($request);
        $result = $this->invoker->invoke($this->definition->name, $payload);

        return json_encode($result, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchemaProperty(JsonSchema $schema, string $type, string $description): array
    {
        return match ($type) {
            'integer' => $schema->integer(description: $description),
            'boolean' => $schema->boolean(description: $description),
            'number' => $schema->number(description: $description),
            default => $schema->string(description: $description),
        };
    }
}
