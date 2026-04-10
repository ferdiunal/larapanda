<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

/**
 * MCP-server-specific invoker wrapper allowing isolated bridge configuration.
 */
final readonly class McpLightpandaToolInvoker
{
    public function __construct(
        private LightpandaToolInvoker $invoker,
    ) {}

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function invoke(string $toolName, array $arguments = []): array
    {
        return $this->invoker->invoke($toolName, $arguments);
    }
}
