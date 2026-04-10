<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Contracts;

use Ferdiunal\Larapanda\Config\InstanceProfile;
use Ferdiunal\Larapanda\Options\FetchOptions;
use Ferdiunal\Larapanda\Options\McpOptions;
use Ferdiunal\Larapanda\Options\ServeOptions;
use Ferdiunal\Larapanda\Requests\PendingFetchRequest;
use Ferdiunal\Larapanda\Requests\PendingMcpRequest;
use Ferdiunal\Larapanda\Requests\PendingServeRequest;
use Ferdiunal\Larapanda\Runtime\FetchResult;
use Ferdiunal\Larapanda\Runtime\RunningInstanceHandle;

/**
 * Contract for executing Lightpanda commands against one instance profile.
 */
interface LarapandaClientInterface
{
    /**
     * Return the immutable profile currently attached to this client.
     */
    public function profile(): InstanceProfile;

    /**
     * Execute a one-shot fetch request.
     *
     * @param  string  $url  Absolute target URL.
     * @param  FetchOptions|null  $options  Method-scoped fetch options.
     */
    public function fetch(string $url, ?FetchOptions $options = null): FetchResult;

    /**
     * Start a fluent fetch request builder.
     *
     * @param  string  $url  Absolute target URL.
     */
    public function fetchRequest(string $url): PendingFetchRequest;

    /**
     * Start the long-running CDP server process.
     *
     * @param  ServeOptions|null  $options  Method-scoped serve options.
     */
    public function serve(?ServeOptions $options = null): RunningInstanceHandle;

    /**
     * Start a fluent serve request builder.
     */
    public function serveRequest(): PendingServeRequest;

    /**
     * Start the long-running MCP process.
     *
     * @param  McpOptions|null  $options  Method-scoped MCP options.
     */
    public function mcp(?McpOptions $options = null): RunningInstanceHandle;

    /**
     * Start a fluent MCP request builder.
     */
    public function mcpRequest(): PendingMcpRequest;
}
