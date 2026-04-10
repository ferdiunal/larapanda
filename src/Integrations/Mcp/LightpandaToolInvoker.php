<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use Ferdiunal\Larapanda\Integrations\Mcp\Contracts\McpBridgeClientInterface;

/**
 * Shared execution service for invoking catalog-defined Lightpanda tools.
 */
final readonly class LightpandaToolInvoker
{
    public function __construct(
        private McpBridgeClientInterface $bridgeClient,
        private LightpandaToolCatalog $catalog,
        private LightpandaToolInputValidator $validator,
    ) {}

    /**
     * Validate and invoke one tool call.
     *
     * @param  string  $toolName  Canonical Lightpanda tool name.
     * @param  array<string, mixed>  $arguments  Caller-provided arguments.
     * @return array<string, mixed>
     */
    public function invoke(string $toolName, array $arguments = []): array
    {
        $definition = $this->catalog->byName($toolName);
        $normalizedArguments = $this->validator->validate($definition, $arguments);

        $sessionId = null;
        if (isset($normalizedArguments['session_id']) && is_string($normalizedArguments['session_id'])) {
            $sessionId = $normalizedArguments['session_id'];
            unset($normalizedArguments['session_id']);
        }

        return $this->bridgeClient->callTool($definition->name, $normalizedArguments, $sessionId);
    }
}
