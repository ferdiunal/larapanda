<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Ai;

use Ferdiunal\Larapanda\Integrations\Mcp\LightpandaToolInvoker;

/**
 * AI-specific invoker wrapper allowing isolated bridge configuration.
 */
final readonly class AiLightpandaToolInvoker
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
