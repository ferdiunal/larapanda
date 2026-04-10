<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Integrations\Exceptions;

use Ferdiunal\Larapanda\Exceptions\LarapandaException;

/**
 * Thrown when optional Laravel AI or MCP integration dependencies are unavailable.
 */
final class MissingLaravelIntegrationDependencyException extends LarapandaException {}
