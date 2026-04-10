<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Exceptions;

use Ferdiunal\Larapanda\Exceptions\LarapandaException;

/**
 * Thrown when MCP JSON-RPC payloads are malformed or protocol-incompatible.
 */
final class McpProtocolException extends LarapandaException {}
