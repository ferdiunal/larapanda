<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp\Tools;

/**
 * MCP adapter for the `scroll` Lightpanda tool.
 */
final class ScrollTool extends AbstractLightpandaMcpTool
{
    /**
     * Return canonical Lightpanda MCP tool name.
     */
    protected function canonicalToolName(): string
    {
        return 'scroll';
    }
}
