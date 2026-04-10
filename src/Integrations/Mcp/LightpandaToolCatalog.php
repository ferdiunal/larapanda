<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use InvalidArgumentException;

/**
 * Central catalog containing Lightpanda MCP tool metadata and input schemas.
 */
final class LightpandaToolCatalog
{
    /**
     * Return all known Lightpanda MCP tool definitions keyed by canonical name.
     *
     * @return array<string, LightpandaToolDefinition>
     */
    public function all(): array
    {
        return [
            'goto' => new LightpandaToolDefinition(
                name: 'goto',
                description: 'Navigate to a URL and load it into session memory.',
                properties: $this->navigationProperties(),
                required: ['url'],
            ),
            'navigate' => new LightpandaToolDefinition(
                name: 'navigate',
                description: 'Alias for goto. Navigate to a URL and load it into session memory.',
                properties: $this->navigationProperties(),
                required: ['url'],
            ),
            'markdown' => new LightpandaToolDefinition(
                name: 'markdown',
                description: 'Return page content in markdown format.',
                properties: $this->navigationProperties(),
            ),
            'links' => new LightpandaToolDefinition(
                name: 'links',
                description: 'Extract links from the current page context.',
                properties: $this->navigationProperties(),
            ),
            'evaluate' => new LightpandaToolDefinition(
                name: 'evaluate',
                description: 'Evaluate JavaScript in the current page context.',
                properties: [
                    'script' => ['type' => 'string', 'description' => 'JavaScript source code to evaluate.'],
                    ...$this->navigationProperties(),
                ],
                required: ['script'],
            ),
            'eval' => new LightpandaToolDefinition(
                name: 'eval',
                description: 'Alias for evaluate. Evaluate JavaScript in the current page context.',
                properties: [
                    'script' => ['type' => 'string', 'description' => 'JavaScript source code to evaluate.'],
                    ...$this->navigationProperties(),
                ],
                required: ['script'],
            ),
            'semantic_tree' => new LightpandaToolDefinition(
                name: 'semantic_tree',
                description: 'Return the simplified semantic DOM tree for reasoning workflows.',
                properties: [
                    ...$this->navigationProperties(),
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Optional node id to scope extraction.'],
                    'maxDepth' => ['type' => 'integer', 'description' => 'Optional maximum tree depth.'],
                ],
            ),
            'nodeDetails' => new LightpandaToolDefinition(
                name: 'nodeDetails',
                description: 'Return detailed node metadata for one backend node identifier.',
                properties: [
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Target backend node identifier.'],
                    ...$this->sessionProperties(),
                ],
                required: ['backendNodeId'],
            ),
            'interactiveElements' => new LightpandaToolDefinition(
                name: 'interactiveElements',
                description: 'List interactive elements available in the current page context.',
                properties: $this->navigationProperties(),
            ),
            'structuredData' => new LightpandaToolDefinition(
                name: 'structuredData',
                description: 'Extract structured metadata such as JSON-LD and OpenGraph payloads.',
                properties: $this->navigationProperties(),
            ),
            'detectForms' => new LightpandaToolDefinition(
                name: 'detectForms',
                description: 'Detect forms and return fields with type and required metadata.',
                properties: $this->navigationProperties(),
            ),
            'click' => new LightpandaToolDefinition(
                name: 'click',
                description: 'Click an element by backend node identifier.',
                properties: [
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Target backend node identifier.'],
                    ...$this->sessionProperties(),
                ],
                required: ['backendNodeId'],
            ),
            'fill' => new LightpandaToolDefinition(
                name: 'fill',
                description: 'Fill a text value into an input field by backend node identifier.',
                properties: [
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Target backend node identifier.'],
                    'text' => ['type' => 'string', 'description' => 'Text value to write.'],
                    ...$this->sessionProperties(),
                ],
                required: ['backendNodeId', 'text'],
            ),
            'scroll' => new LightpandaToolDefinition(
                name: 'scroll',
                description: 'Scroll the page or a specific element.',
                properties: [
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Optional target element backend node id.'],
                    'x' => ['type' => 'integer', 'description' => 'Horizontal scroll offset.'],
                    'y' => ['type' => 'integer', 'description' => 'Vertical scroll offset.'],
                    ...$this->sessionProperties(),
                ],
            ),
            'waitForSelector' => new LightpandaToolDefinition(
                name: 'waitForSelector',
                description: 'Wait for a selector to appear in the page and return matched node metadata.',
                properties: [
                    'selector' => ['type' => 'string', 'description' => 'CSS selector to wait for.'],
                    'timeout' => ['type' => 'integer', 'description' => 'Optional timeout override in milliseconds.'],
                    ...$this->sessionProperties(),
                ],
                required: ['selector'],
            ),
            'hover' => new LightpandaToolDefinition(
                name: 'hover',
                description: 'Hover over an element by backend node identifier.',
                properties: [
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Target backend node identifier.'],
                    ...$this->sessionProperties(),
                ],
                required: ['backendNodeId'],
            ),
            'press' => new LightpandaToolDefinition(
                name: 'press',
                description: 'Press a keyboard key in the current page context.',
                properties: [
                    'key' => ['type' => 'string', 'description' => 'Keyboard key value.'],
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Optional target backend node identifier.'],
                    ...$this->sessionProperties(),
                ],
                required: ['key'],
            ),
            'selectOption' => new LightpandaToolDefinition(
                name: 'selectOption',
                description: 'Select an option value in a <select> element.',
                properties: [
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Target select element backend node identifier.'],
                    'value' => ['type' => 'string', 'description' => 'Option value to select.'],
                    ...$this->sessionProperties(),
                ],
                required: ['backendNodeId', 'value'],
            ),
            'setChecked' => new LightpandaToolDefinition(
                name: 'setChecked',
                description: 'Set checkbox or radio checked state by backend node identifier.',
                properties: [
                    'backendNodeId' => ['type' => 'integer', 'description' => 'Target input backend node identifier.'],
                    'checked' => ['type' => 'boolean', 'description' => 'Checked state to apply.'],
                    ...$this->sessionProperties(),
                ],
                required: ['backendNodeId', 'checked'],
            ),
            'findElement' => new LightpandaToolDefinition(
                name: 'findElement',
                description: 'Find elements by role and/or accessible name.',
                properties: [
                    'role' => ['type' => 'string', 'description' => 'Optional ARIA role filter.'],
                    'name' => ['type' => 'string', 'description' => 'Optional accessible name filter.'],
                    ...$this->sessionProperties(),
                ],
            ),
        ];
    }

    /**
     * Resolve one tool definition by canonical name.
     */
    public function byName(string $name): LightpandaToolDefinition
    {
        $tool = $this->all()[$name] ?? null;
        if (! $tool instanceof LightpandaToolDefinition) {
            throw new InvalidArgumentException("Unknown Lightpanda tool [{$name}].");
        }

        return $tool;
    }

    /**
     * Resolve a filtered subset preserving input order.
     *
     * @param  list<string>  $toolNames
     * @return list<LightpandaToolDefinition>
     */
    public function only(array $toolNames): array
    {
        $definitions = [];

        foreach ($toolNames as $toolName) {
            $definitions[] = $this->byName($toolName);
        }

        return $definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function navigationProperties(): array
    {
        return [
            'url' => ['type' => 'string', 'description' => 'Optional URL to navigate to before execution.'],
            'timeout' => ['type' => 'integer', 'description' => 'Optional timeout in milliseconds.'],
            'waitUntil' => ['type' => 'string', 'description' => 'Optional navigation wait strategy.'],
            ...$this->sessionProperties(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sessionProperties(): array
    {
        return [
            'session_id' => ['type' => 'string', 'description' => 'Optional session key for persistent context.'],
        ];
    }
}
