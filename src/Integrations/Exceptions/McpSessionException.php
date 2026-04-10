<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Exceptions;

use Ferdiunal\Larapanda\Exceptions\LarapandaException;

/**
 * Thrown when MCP session state cannot be created, reused, or released safely.
 */
final class McpSessionException extends LarapandaException {}
