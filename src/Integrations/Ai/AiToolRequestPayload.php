<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Ai;

/**
 * Normalizes AI SDK tool request objects into plain arrays.
 */
final class AiToolRequestPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function fromRequest(mixed $request): array
    {
        if (is_array($request)) {
            /** @var array<string, mixed> $request */
            return $request;
        }

        if (is_object($request)) {
            if (method_exists($request, 'toArray')) {
                $payload = $request->toArray();
                if (is_array($payload)) {
                    /** @var array<string, mixed> $payload */
                    return $payload;
                }
            }

            if (method_exists($request, 'all')) {
                $payload = $request->all();
                if (is_array($payload)) {
                    /** @var array<string, mixed> $payload */
                    return $payload;
                }
            }

            if (method_exists($request, 'input')) {
                $payload = $request->input();
                if (is_array($payload)) {
                    /** @var array<string, mixed> $payload */
                    return $payload;
                }
            }
        }

        return [];
    }
}
