<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Mcp;

use InvalidArgumentException;

/**
 * Runtime validator for tool arguments based on catalog definition metadata.
 */
final class LightpandaToolInputValidator
{
    /**
     * Validate and normalize incoming tool arguments.
     *
     * @param  LightpandaToolDefinition  $definition  Tool definition metadata.
     * @param  array<string, mixed>  $arguments  Caller-provided arguments.
     * @return array<string, mixed> Normalized arguments preserving allowed keys only.
     */
    public function validate(LightpandaToolDefinition $definition, array $arguments): array
    {
        $normalized = [];

        foreach ($definition->required as $required) {
            if (! array_key_exists($required, $arguments) || $arguments[$required] === null || $arguments[$required] === '') {
                throw new InvalidArgumentException("Required tool argument [{$required}] is missing for [{$definition->name}].");
            }
        }

        foreach ($arguments as $key => $value) {
            if (! isset($definition->properties[$key])) {
                continue;
            }

            $type = isset($definition->properties[$key]['type']) && is_string($definition->properties[$key]['type'])
                ? $definition->properties[$key]['type']
                : '';
            if (! $this->isValidType($value, $type)) {
                throw new InvalidArgumentException("Tool argument [{$key}] must be of type [{$type}] for [{$definition->name}].");
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function isValidType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            'number' => is_int($value) || is_float($value),
            'array' => is_array($value),
            default => true,
        };
    }
}
