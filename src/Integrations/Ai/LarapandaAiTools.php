<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Ai;

use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolCatalog;
use Laravel\Ai\Contracts\Tool;

/**
 * Registry/factory for AI SDK tool adapters backed by Lightpanda MCP commands.
 */
final readonly class LarapandaAiTools
{
    /**
     * @param  LightpandaToolCatalog  $catalog  Canonical tool metadata catalog.
     * @param  AiLightpandaToolInvoker  $invoker  Tool execution service.
     * @param  string  $namePrefix  AI tool name prefix (default `lightpanda_`).
     * @param  list<string>  $exposedTools  Subset of tool names exposed to AI models.
     */
    public function __construct(
        private LightpandaToolCatalog $catalog,
        private AiLightpandaToolInvoker $invoker,
        private string $namePrefix = 'lightpanda_',
        private array $exposedTools = [],
    ) {}

    /**
     * Create all configured AI SDK tools.
     *
     * @return list<Tool>
     */
    public function make(): array
    {
        $definitions = $this->exposedTools === []
            ? array_values($this->catalog->all())
            : $this->catalog->only($this->exposedTools);

        $tools = [];

        foreach ($definitions as $definition) {
            $tools[] = new LightpandaAiTool(
                namePrefix: $this->namePrefix,
                definition: $definition,
                invoker: $this->invoker,
            );
        }

        return $tools;
    }
}
