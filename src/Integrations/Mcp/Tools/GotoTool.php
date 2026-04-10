<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp\Tools;

/**
 * MCP adapter for the `goto` Lightpanda tool.
 */
final class GotoTool extends AbstractLightpandaMcpTool
{
    /**
     * Return canonical Lightpanda MCP tool name.
     */
    protected function canonicalToolName(): string
    {
        return 'goto';
    }
}
