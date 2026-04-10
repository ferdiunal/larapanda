<?php

declare(strict_types=1);

use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolCatalog;
use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolDefinition;

/**
 * Validate Lightpanda MCP tool catalog coverage and lookup behavior.
 */
it('contains all expected canonical mcp tools', function (): void {
    $catalog = new LightpandaToolCatalog;
    $tools = $catalog->all();

    expect($tools)->toHaveKeys([
        'goto',
        'navigate',
        'markdown',
        'links',
        'evaluate',
        'eval',
        'semantic_tree',
        'nodeDetails',
        'interactiveElements',
        'structuredData',
        'detectForms',
        'click',
        'fill',
        'scroll',
        'waitForSelector',
        'hover',
        'press',
        'selectOption',
        'setChecked',
        'findElement',
    ]);
});

/**
 * Ensure catalog lookup returns a typed definition for known tools.
 */
it('resolves tool definition by canonical name', function (): void {
    $catalog = new LightpandaToolCatalog;
    $definition = $catalog->byName('markdown');

    expect($definition)->toBeInstanceOf(LightpandaToolDefinition::class)
        ->and($definition->name)->toBe('markdown')
        ->and($definition->description)->toContain('markdown');
});

/**
 * Ensure unknown tool lookups are rejected deterministically.
 */
it('throws for unknown tool lookup', function (): void {
    $catalog = new LightpandaToolCatalog;

    expect(fn (): LightpandaToolDefinition => $catalog->byName('unknown-tool'))
        ->toThrow(InvalidArgumentException::class);
});
