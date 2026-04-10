# Larapanda

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ferdiunal/larapanda.svg?style=flat-square)](https://packagist.org/packages/ferdiunal/larapanda)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ferdiunal/larapanda/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ferdiunal/larapanda/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ferdiunal/larapanda/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ferdiunal/larapanda/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ferdiunal/larapanda.svg?style=flat-square)](https://packagist.org/packages/ferdiunal/larapanda)

![Larapanda](larapanda.png)

Language versions: [English](README.md) | [Türkçe](README.TR.md)

Larapanda is a type-safe Lightpanda SDK for Laravel and plain PHP applications. It provides named instance profiles, runtime resolution (`auto`, `cli`, `docker`), and immutable method-scoped option objects for `fetch`, `serve`, and `mcp`.

## Installation

### Prerequisites

Larapanda uses a runtime resolver (`auto`, `cli`, `docker`). CLI execution (and `auto` mode when CLI is selected) requires a valid Lightpanda binary. Install Lightpanda from the official source: [lightpanda.io](https://lightpanda.io/).

```bash
curl -fsSL https://pkg.lightpanda.io/install.sh | bash
```

Install the Larapanda SDK package:

```bash
composer require ferdiunal/larapanda
```

For Laravel applications, publish the package configuration:

```bash
php artisan vendor:publish --tag="larapanda-config"
```

Optional integration dependencies:

```bash
composer require laravel/ai laravel/mcp
```

## Configuration

Configuration is profile-based. Each client resolves an instance profile by name, and each profile may override the global defaults.

```php
return [
    'default_instance' => 'default',

    'defaults' => [
        'runtime' => 'auto',
        'binary_path' => env('LARAPANDA_BINARY_PATH'),
        'docker' => [
            'command' => 'docker',
            'image' => 'lightpanda/browser:nightly',
            'container_name' => 'larapanda-lightpanda',
            'remove' => true,
            'extra_args' => [],
        ],
    ],

    'instances' => [
        'default' => [],
        'crawler' => [
            // For strict CLI mode:
            // 'runtime' => 'cli',
            // 'binary_path' => '/absolute/path/to/lightpanda',
        ],
        'mcp' => [
            // For strict Docker mode:
            // 'runtime' => 'docker',
        ],
    ],
];
```

- `default_instance` selects the instance profile used when no explicit profile name is provided.
- `defaults` defines baseline runtime settings shared by all instances unless overridden.
- `instances.<name>` contains per-profile overrides for runtime mode, binary path, Docker parameters, and process options.
- In `auto` runtime mode, Larapanda prefers CLI execution when `binary_path` is available and executable; otherwise it falls back to Docker.
- `integrations.ai` configures AI SDK tool adapters (`instance`, `tool_prefix`, `exposed_tools`, session settings).
- `integrations.mcp` configures Laravel MCP server tool adapters (`instance`, `exposed_tools`, session settings).

## Usage

### Scenario 1: Quickstart Fetch

Resolve the manager, select an instance profile, and execute a markdown fetch.

```php
use Ferdiunal\Larapanda\Contracts\LarapandaManagerInterface;
use Ferdiunal\Larapanda\Enums\FetchDumpFormat;

$manager = app(LarapandaManagerInterface::class);
$client = $manager->instance('default');

$fetch = $client->fetchRequest('https://example.com')
    ->withOptions(
        dump: FetchDumpFormat::Markdown,
        obeyRobots: true,
        waitMs: 2000,
    )
    ->run();

$markdown = $fetch->asMarkdown();
```

### Scenario 2: Output Modes

Use strict typed accessors based on the selected dump format.

```php
use Ferdiunal\Larapanda\Enums\FetchDumpFormat;

$markdownResult = $client->fetchRequest('https://example.com')
    ->withOptions(
        dump: FetchDumpFormat::Markdown,
    )
    ->run();

$semanticTreeResult = $client->fetchRequest('https://example.com')
    ->withOptions(
        dump: FetchDumpFormat::SemanticTree,
    )
    ->run();

$semanticTreeTextResult = $client->fetchRequest('https://example.com')
    ->withOptions(
        dump: FetchDumpFormat::SemanticTreeText,
    )
    ->run();

$markdown = $markdownResult->asMarkdown();
$semanticTree = $semanticTreeResult->asSemanticTree();        // array<string, mixed>
$semanticTreeText = $semanticTreeTextResult->asSemanticTreeText();
$rawOutput = $semanticTreeTextResult->output();               // raw stdout fallback
```

`FetchResult` throws `UnexpectedFetchOutputFormatException` when a strict accessor does not match the selected dump format.

### Scenario 3: Named Instance Profiles

Select the profile according to runtime intent:

- `default`: baseline fetch workloads.
- `crawler`: stricter crawl profile (for example, dedicated CLI runtime settings).
- `mcp`: MCP-oriented profile for long-running interactive sessions.

```php
$defaultClient = $manager->instance('default');
$crawlerClient = $manager->instance('crawler');
$mcpClient = $manager->instance('mcp');
```

### Scenario 4: Proxy-Aware Fetch

Use request-level proxy settings for one fetch operation:

```php
use Ferdiunal\Larapanda\Enums\FetchDumpFormat;

$fetch = $client->fetchRequest('https://example.com')
    ->withOptions(
        dump: FetchDumpFormat::Markdown,
        httpProxy: 'http://127.0.0.1:3000',
        proxyBearerToken: 'MY-TOKEN',
    )
    ->run();
```

Use integration-level proxy settings for AI SDK and MCP server tool sessions:

```php
// config/larapanda.php
'integrations' => [
    'ai' => [
        'http_proxy' => 'http://127.0.0.1:3000',
        'proxy_bearer_token' => 'MY-TOKEN',
    ],
    'mcp' => [
        'http_proxy' => 'http://127.0.0.1:3000',
        'proxy_bearer_token' => 'MY-TOKEN',
    ],
],
```

### Scenario 5: Long-Running Serve and MCP Modes

Use `RunningInstanceHandle` lifecycle methods for process-safe execution:

```php
$serveHandle = $client->serveRequest()
    ->withOptions(host: '127.0.0.1', port: 9222)
    ->run();

try {
    if ($serveHandle->isRunning()) {
        // connect with CDP client
    }
} finally {
    $serveHandle->stop();
    $serveHandle->wait(2.0);
}

$mcpHandle = $client->mcpRequest()->run();

try {
    if ($mcpHandle->isRunning()) {
        // attach MCP host/client
    }
} finally {
    $mcpHandle->stop();
    $mcpHandle->wait(2.0);
}
```

## Common Patterns

```php
// Robots-compliant fetch
$result = $client->fetchRequest('https://example.com')
    ->withOptions(obeyRobots: true, dump: FetchDumpFormat::Markdown)
    ->run();

// Strict accessor mismatch raises UnexpectedFetchOutputFormatException
$result->asSemanticTreeText();
```

## Laravel AI SDK Tools

Install optional dependencies before using adapter features:

```bash
composer require laravel/ai laravel/mcp
```

Larapanda exposes AI SDK-compatible tools via `LarapandaAiTools`. The adapter is MCP-backed, session-aware, and config-driven.

### Scenario 1: Full Tool Catalog

```php
use Ferdiunal\Larapanda\Integrations\Ai\LarapandaAiTools;
use Illuminate\Support\Facades\AI;

$response = AI::provider('openai')
    ->model('gpt-5-mini')
    ->prompt('Open laravel.com and return the main headings.')
    ->tools(app(LarapandaAiTools::class)->make())
    ->text();
```

Tool naming uses the configured prefix (default `lightpanda_`), for example: `lightpanda_markdown`, `lightpanda_semantic_tree`, `lightpanda_click`.

### Scenario 2: Restrict Exposed AI Tools

Limit model access to a subset of tool surfaces:

```php
// config/larapanda.php
'integrations' => [
    'ai' => [
        'exposed_tools' => ['goto', 'markdown', 'semantic_tree'],
    ],
],
```

### Scenario 3: Session-Aware AI Tool Continuity

For multi-step browsing tasks, guide the model to reuse a stable `session_id` across tool calls.

```php
$response = AI::provider('openai')
    ->model('gpt-5-mini')
    ->prompt('Use lightpanda tools with session_id=\"docs-session\". First goto laravel.com, then return markdown.')
    ->tools(app(LarapandaAiTools::class)->make())
    ->text();
```

## Laravel MCP Server (Optional / Advanced)

Use this layer when you want Laravel-managed MCP registration, container wiring, config-based tool filtering, and shared session/proxy policies.  
If you only need a standalone MCP binary host, native `lightpanda mcp` can be used directly without Larapanda adapter classes.

### Native vs Adapter Decision Matrix

| Use case | Recommended path |
| --- | --- |
| Lowest-level MCP host integration over stdio | Native `lightpanda mcp` |
| Laravel container wiring + profile-based runtime resolution | Larapanda MCP adapter |
| Config-driven tool exposure (`integrations.mcp.exposed_tools`) | Larapanda MCP adapter |
| Shared session pool and proxy policy reused by AI SDK tools | Larapanda MCP adapter |
| Direct protocol troubleshooting against Lightpanda itself | Native `lightpanda mcp` |

### Scenario 1: Register MCP Tools in `routes/ai.php`

```php
use Ferdiunal\Larapanda\Integrations\Mcp\LarapandaMcpServer;

LarapandaMcpServer::registerLocal(name: 'lightpanda');
```

### Scenario 2: Restrict MCP Tool Exposure

By default, all Lightpanda MCP tools are exposed. To reduce tool surface:

```php
// config/larapanda.php
'integrations' => [
    'mcp' => [
        'exposed_tools' => ['goto', 'markdown', 'semantic_tree'],
    ],
],
```

### Scenario 3: Session and Proxy Policy for MCP Adapter

Session and proxy behavior are controlled from config:

- Each integration path (AI and MCP server) maintains an isolated in-memory session pool.
- Sessions map to long-running `lightpanda mcp` processes.
- Sessions expire using `session_ttl_seconds` and are capped by `max_sessions`.
- `session_id` can be passed in tool arguments to preserve page context across calls.

```php
// config/larapanda.php
'integrations' => [
    'mcp' => [
        'session_ttl_seconds' => 300,
        'max_sessions' => 32,
        'obey_robots' => true,
        'http_proxy' => 'http://127.0.0.1:3000',
        'proxy_bearer_token' => 'MY-TOKEN',
    ],
],
```

### Scenario 4: Interactive MCP Argument Model (Native-Aligned)

Interactive tools use backend-node-driven arguments. The typical flow is:

1. Navigate and discover target nodes (`goto` + `waitForSelector` or `interactiveElements`).
2. Extract `backendNodeId` from the response payload.
3. Execute interaction tools with that node id on the same `session_id`.

```php
// Example argument shapes (canonical tool contracts):
// goto|navigate:      ['url' => 'https://example.com', 'timeout' => 10000, 'waitUntil' => 'done']
// waitForSelector:    ['selector' => '#submit', 'timeout' => 5000]
// click|hover:        ['backendNodeId' => 123]
// fill:               ['backendNodeId' => 123, 'text' => 'Ferdi']
// press:              ['key' => 'Enter', 'backendNodeId' => 123] // backendNodeId optional
// selectOption:       ['backendNodeId' => 123, 'value' => 'tr']
// setChecked:         ['backendNodeId' => 123, 'checked' => true]
// scroll:             ['y' => 400, 'backendNodeId' => 123] // backendNodeId optional
```

## Testing

Run the full test suite:

```bash
composer test
```

Run opt-in live CLI + MCP smoke tests (native MCP and Larapanda bridge):

```bash
LIGHTPANDA_LIVE_TESTS=1 \
LIGHTPANDA_BINARY_PATH=/Users/ferdiunal/Web/larapanda/lightpanda \
php vendor/bin/pest --group=live
```

Run MCP-focused live smoke tests only:

```bash
LIGHTPANDA_LIVE_TESTS=1 \
LIGHTPANDA_BINARY_PATH=/Users/ferdiunal/Web/larapanda/lightpanda \
php vendor/bin/pest --filter=Mcp --group=live
```

Live suite policy:

- Live tests are opt-in and are not required in the default CI pipeline.
- In constrained environments, DNS/port limitations result in deterministic `skip`, not flaky `fail`.
- Real protocol and argument contract mismatches continue to fail.

Prerequisites for live tests:

- `LIGHTPANDA_BINARY_PATH` points to a valid executable Lightpanda binary.
- The environment permits internet access and local port binding.
- Optional proxy smoke test requires `LIGHTPANDA_HTTP_PROXY` (and optional `LIGHTPANDA_PROXY_BEARER_TOKEN`).

Optional proxy smoke run:

```bash
LIGHTPANDA_LIVE_TESTS=1 \
LIGHTPANDA_BINARY_PATH=/Users/ferdiunal/Web/larapanda/lightpanda \
LIGHTPANDA_HTTP_PROXY=http://127.0.0.1:3000 \
LIGHTPANDA_PROXY_BEARER_TOKEN=YOUR_TOKEN \
php vendor/bin/pest --group=live
```

## Credits

- [Ferdi ÜNAL](https://github.com/ferdiunal)

## License

This package is licensed under the MIT License. See [LICENSE.md](LICENSE.md) for details.
