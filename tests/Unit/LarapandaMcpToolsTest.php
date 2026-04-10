<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Integrations\Mcp\LarapandaMcpTools;

/**
 * Validate MCP tool class registry helper behavior.
 */
it('returns filtered tool classes by canonical names', function (): void {
    $tools = LarapandaMcpTools::only(['goto', 'markdown']);

    expect($tools)->toHaveCount(2)
        ->and($tools[0])->toContain('GotoTool')
        ->and($tools[1])->toContain('MarkdownTool');
});

/**
 * Ensure full MCP registry includes native-aligned adapters.
 */
it('returns full mcp tool registry with native-aligned aliases', function (): void {
    $tools = LarapandaMcpTools::map();

    expect($tools)->toHaveKeys([
        'goto',
        'navigate',
        'evaluate',
        'eval',
        'nodeDetails',
        'hover',
        'press',
        'selectOption',
        'setChecked',
        'findElement',
    ]);
});

/**
 * Ensure unknown tool names are rejected by registry helper.
 */
it('throws for unknown filtered mcp tool names', function (): void {
    expect(fn (): array => LarapandaMcpTools::only(['unknown']))
        ->toThrow(InvalidArgumentException::class);
});
