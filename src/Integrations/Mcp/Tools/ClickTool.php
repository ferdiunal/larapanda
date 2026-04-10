<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp\Tools;

/**
 * MCP adapter for the `click` Lightpanda tool.
 */
final class ClickTool extends AbstractLightpandaMcpTool
{
    /**
     * Return canonical Lightpanda MCP tool name.
     */
    protected function canonicalToolName(): string
    {
        return 'click';
    }
}
