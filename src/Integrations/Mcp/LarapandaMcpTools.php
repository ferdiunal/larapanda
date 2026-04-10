<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use Ferdiunal\Larapanda\Integrations\Mcp\Tools\ClickTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\DetectFormsTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\EvalTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\EvaluateTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\FillTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\FindElementTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\GotoTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\HoverTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\InteractiveElementsTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\LinksTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\MarkdownTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\NavigateTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\NodeDetailsTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\PressTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\ScrollTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\SelectOptionTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\SemanticTreeTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\SetCheckedTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\StructuredDataTool;
use Ferdiunal\Larapanda\Integrations\Mcp\Tools\WaitForSelectorTool;
use InvalidArgumentException;

/**
 * Canonical Laravel MCP tool class registry for Lightpanda operations.
 */
final class LarapandaMcpTools
{
    /**
     * Return class map keyed by canonical Lightpanda tool name.
     *
     * @return array<string, class-string>
     */
    public static function map(): array
    {
        return [
            'goto' => GotoTool::class,
            'navigate' => NavigateTool::class,
            'markdown' => MarkdownTool::class,
            'links' => LinksTool::class,
            'evaluate' => EvaluateTool::class,
            'eval' => EvalTool::class,
            'semantic_tree' => SemanticTreeTool::class,
            'nodeDetails' => NodeDetailsTool::class,
            'interactiveElements' => InteractiveElementsTool::class,
            'structuredData' => StructuredDataTool::class,
            'detectForms' => DetectFormsTool::class,
            'click' => ClickTool::class,
            'fill' => FillTool::class,
            'scroll' => ScrollTool::class,
            'waitForSelector' => WaitForSelectorTool::class,
            'hover' => HoverTool::class,
            'press' => PressTool::class,
            'selectOption' => SelectOptionTool::class,
            'setChecked' => SetCheckedTool::class,
            'findElement' => FindElementTool::class,
        ];
    }

    /**
     * Return all Lightpanda MCP server tool classes.
     *
     * @return list<class-string>
     */
    public static function all(): array
    {
        return array_values(self::map());
    }

    /**
     * Resolve configured subset of tool classes.
     *
     * @param  list<string>  $toolNames
     * @return list<class-string>
     */
    public static function only(array $toolNames): array
    {
        $map = self::map();
        $classes = [];

        foreach ($toolNames as $toolName) {
            $class = $map[$toolName] ?? null;
            if (! is_string($class)) {
                throw new InvalidArgumentException("Unknown Lightpanda MCP tool [{$toolName}].");
            }

            $classes[] = $class;
        }

        return $classes;
    }
}
