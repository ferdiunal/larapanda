# Larapanda

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ferdiunal/larapanda.svg?style=flat-square)](https://packagist.org/packages/ferdiunal/larapanda)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ferdiunal/larapanda/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ferdiunal/larapanda/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ferdiunal/larapanda/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ferdiunal/larapanda/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ferdiunal/larapanda.svg?style=flat-square)](https://packagist.org/packages/ferdiunal/larapanda)

![Larapanda](larapanda.png)

Dil sürümleri: [English](README.md) | [Türkçe](README.TR.md)

Larapanda, Laravel ve düz PHP uygulamaları için type-safe bir Lightpanda SDK'sıdır. `fetch`, `serve` ve `mcp` işlemleri için adlandırılmış instance profile yapısı, runtime çözümleme (`auto`, `cli`, `docker`) ve immutable method-scoped option nesneleri sunar.

## Kurulum

### Önkoşullar

Larapanda bir runtime resolver (`auto`, `cli`, `docker`) kullanır. CLI yürütmesi (ve `auto` modunda CLI seçildiğinde) geçerli bir Lightpanda binary dosyası gerektirir. Lightpanda'yı resmi kaynaktan kurun: [lightpanda.io](https://lightpanda.io/).

```bash
curl -fsSL https://pkg.lightpanda.io/install.sh | bash
```

Larapanda SDK paketini yükleyin:

```bash
composer require ferdiunal/larapanda
```

Laravel uygulamaları için paket yapılandırmasını publish edin:

```bash
php artisan vendor:publish --tag="larapanda-config"
```

Opsiyonel entegrasyon bağımlılıkları:

```bash
composer require laravel/ai laravel/mcp
```

## Yapılandırma

Yapılandırma profile tabanlıdır. Her client, instance profile'ını adıyla çözümler ve her profile global defaults değerlerini override edebilir.

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

- `default_instance`, açık profile adı verilmediğinde kullanılacak instance profile'ını seçer.
- `defaults`, override edilmediği sürece tüm instance'lar tarafından paylaşılan temel runtime ayarlarını tanımlar.
- `instances.<name>`, runtime mode, binary path, Docker parametreleri ve process options için profile bazlı override değerlerini içerir.
- `auto` runtime mode'da Larapanda, `binary_path` mevcut ve executable ise CLI yürütmesini tercih eder; aksi durumda Docker fallback uygular.
- `integrations.ai`, AI SDK tool adapter ayarlarını yapılandırır (`instance`, `tool_prefix`, `exposed_tools`, session ayarları).
- `integrations.mcp`, Laravel MCP server tool adapter ayarlarını yapılandırır (`instance`, `exposed_tools`, session ayarları).

## Kullanım

### Senaryo 1: Hızlı Başlangıç Fetch

Manager'ı çözümleyin, bir instance profile seçin ve markdown fetch çalıştırın.

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

### Senaryo 2: Çıktı Modları

Seçilen dump formatına göre strict typed accessor'ları kullanın.

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

`FetchResult`, strict accessor seçilen dump formatıyla eşleşmediğinde `UnexpectedFetchOutputFormatException` fırlatır.

### Senaryo 3: Adlandırılmış Instance Profile'ları

Runtime amacına göre profile seçin:

- `default`: temel fetch iş yükleri.
- `crawler`: daha sıkı crawl profile'ı (örneğin, ayrılmış CLI runtime ayarları).
- `mcp`: uzun ömürlü etkileşimli oturumlar için MCP odaklı profile.

```php
$defaultClient = $manager->instance('default');
$crawlerClient = $manager->instance('crawler');
$mcpClient = $manager->instance('mcp');
```

### Senaryo 4: Proxy Farkındalıklı Fetch

Tek bir fetch işlemi için request-level proxy ayarları kullanın:

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

AI SDK ve MCP server tool oturumları için integration-level proxy ayarlarını kullanın:

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

### Senaryo 5: Uzun Ömürlü Serve ve MCP Modları

Process-safe yürütme için `RunningInstanceHandle` lifecycle method'larını kullanın:

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

## Yaygın Desenler

```php
// Robots-compliant fetch
$result = $client->fetchRequest('https://example.com')
    ->withOptions(obeyRobots: true, dump: FetchDumpFormat::Markdown)
    ->run();

// Strict accessor mismatch raises UnexpectedFetchOutputFormatException
$result->asSemanticTreeText();
```

## Laravel AI SDK Tool'ları

Adapter özelliklerini kullanmadan önce opsiyonel bağımlılıkları yükleyin:

```bash
composer require laravel/ai laravel/mcp
```

Larapanda, `LarapandaAiTools` üzerinden AI SDK uyumlu tool'lar sunar. Adapter MCP tabanlı, session-aware ve config-driven olarak çalışır.

### Senaryo 1: Tam Tool Kataloğu

```php
use Ferdiunal\Larapanda\Integrations\Ai\LarapandaAiTools;
use Illuminate\Support\Facades\AI;

$response = AI::provider('openai')
    ->model('gpt-5-mini')
    ->prompt('Open laravel.com and return the main headings.')
    ->tools(app(LarapandaAiTools::class)->make())
    ->text();
```

Tool adlandırması yapılandırılmış prefix'i kullanır (varsayılan `lightpanda_`), örneğin: `lightpanda_markdown`, `lightpanda_semantic_tree`, `lightpanda_click`.

### Senaryo 2: Dışa Açık AI Tool'larını Sınırlandırma

Model erişimini tool yüzeyinin bir alt kümesiyle sınırlayın:

```php
// config/larapanda.php
'integrations' => [
    'ai' => [
        'exposed_tools' => ['goto', 'markdown', 'semantic_tree'],
    ],
],
```

### Senaryo 3: Session-Aware AI Tool Sürekliliği

Çok adımlı gezinme görevlerinde modeli, tool çağrıları arasında kararlı bir `session_id` yeniden kullanacak şekilde yönlendirin.

```php
$response = AI::provider('openai')
    ->model('gpt-5-mini')
    ->prompt('Use lightpanda tools with session_id=\"docs-session\". First goto laravel.com, then return markdown.')
    ->tools(app(LarapandaAiTools::class)->make())
    ->text();
```

## Laravel MCP Server (Opsiyonel / İleri Düzey)

Bu katmanı, Laravel tarafından yönetilen MCP kaydı, container wiring, config tabanlı tool filtreleme ve ortak session/proxy policy'leri istediğinizde kullanın.  
Yalnızca bağımsız bir MCP binary host'u gerekiyorsa yerel `lightpanda mcp`, Larapanda adapter sınıfları olmadan doğrudan kullanılabilir.

### Native vs Adapter Karar Matrisi

| Kullanım durumu | Önerilen yol |
| --- | --- |
| En düşük katmanda stdio üzerinden MCP host entegrasyonu | Native `lightpanda mcp` |
| Laravel container wiring + profile tabanlı runtime çözümleme | Larapanda MCP adapter |
| Config tabanlı tool exposure (`integrations.mcp.exposed_tools`) | Larapanda MCP adapter |
| AI SDK tool'larıyla paylaşılan session pool ve proxy policy | Larapanda MCP adapter |
| Lightpanda protokol seviyesinde doğrudan troubleshooting | Native `lightpanda mcp` |

### Senaryo 1: `routes/ai.php` İçinde MCP Tool Kaydı

```php
use Ferdiunal\Larapanda\Integrations\Mcp\LarapandaMcpServer;

LarapandaMcpServer::registerLocal(name: 'lightpanda');
```

### Senaryo 2: MCP Tool Exposure Sınırlandırma

Varsayılan olarak tüm Lightpanda MCP tool'ları açığa çıkarılır. Tool yüzeyini daraltmak için:

```php
// config/larapanda.php
'integrations' => [
    'mcp' => [
        'exposed_tools' => ['goto', 'markdown', 'semantic_tree'],
    ],
],
```

### Senaryo 3: MCP Adapter için Session ve Proxy Policy

Session ve proxy davranışı config üzerinden kontrol edilir:

- Her integration yolu (AI ve MCP server), izole bir in-memory session pool'u tutar.
- Session'lar uzun ömürlü `lightpanda mcp` process'lerine eşlenir.
- Session'lar `session_ttl_seconds` ile sonlandırılır ve `max_sessions` ile sınırlandırılır.
- Tool argümanlarında `session_id` gönderilerek çağrılar arasında sayfa bağlamı korunabilir.

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

### Senaryo 4: İnteraktif MCP Argüman Modeli (Native Uyumlu)

İnteraktif tool'lar backend-node tabanlı argüman modeli kullanır. Tipik akış:

1. Hedef node'ları keşfet (`goto` + `waitForSelector` veya `interactiveElements`).
2. Yanıttan `backendNodeId` değerini çıkar.
3. Aynı `session_id` içinde etkileşim tool'larını bu node id ile çağır.

```php
// Canonical tool argüman şekilleri:
// goto|navigate:      ['url' => 'https://example.com', 'timeout' => 10000, 'waitUntil' => 'done']
// waitForSelector:    ['selector' => '#submit', 'timeout' => 5000]
// click|hover:        ['backendNodeId' => 123]
// fill:               ['backendNodeId' => 123, 'text' => 'Ferdi']
// press:              ['key' => 'Enter', 'backendNodeId' => 123] // backendNodeId opsiyonel
// selectOption:       ['backendNodeId' => 123, 'value' => 'tr']
// setChecked:         ['backendNodeId' => 123, 'checked' => true]
// scroll:             ['y' => 400, 'backendNodeId' => 123] // backendNodeId opsiyonel
```

## Test

Tam test paketini çalıştırın:

```bash
composer test
```

Opt-in canlı CLI + MCP smoke testlerini çalıştırın (native MCP + Larapanda bridge):

```bash
LIGHTPANDA_LIVE_TESTS=1 \
LIGHTPANDA_BINARY_PATH=/Users/ferdiunal/Web/larapanda/lightpanda \
php vendor/bin/pest --group=live
```

Sadece MCP odaklı canlı smoke testlerini çalıştırın:

```bash
LIGHTPANDA_LIVE_TESTS=1 \
LIGHTPANDA_BINARY_PATH=/Users/ferdiunal/Web/larapanda/lightpanda \
php vendor/bin/pest --filter=Mcp --group=live
```

Canlı test politikası:

- Canlı testler opt-in'dir; varsayılan CI pipeline'ında zorunlu değildir.
- Kısıtlı ortamlarda DNS/port limitleri flaky `fail` yerine deterministic `skip` üretir.
- Gerçek protokol ve argüman sözleşmesi uyumsuzlukları yine `fail` olur.

Canlı testler için önkoşullar:

- `LIGHTPANDA_BINARY_PATH`, geçerli ve executable bir Lightpanda binary dosyasını işaret etmelidir.
- Ortam, internet erişimi ve local port binding için uygun olmalıdır.
- Opsiyonel proxy smoke senaryosu için `LIGHTPANDA_HTTP_PROXY` (isteğe bağlı `LIGHTPANDA_PROXY_BEARER_TOKEN`) gereklidir.

Opsiyonel proxy smoke çalıştırma:

```bash
LIGHTPANDA_LIVE_TESTS=1 \
LIGHTPANDA_BINARY_PATH=/Users/ferdiunal/Web/larapanda/lightpanda \
LIGHTPANDA_HTTP_PROXY=http://127.0.0.1:3000 \
LIGHTPANDA_PROXY_BEARER_TOKEN=YOUR_TOKEN \
php vendor/bin/pest --group=live
```

## Katkı Sağlayan

- [Ferdi ÜNAL](https://github.com/ferdiunal)

## Lisans

Bu paket MIT Lisansı altında lisanslanmıştır. Ayrıntılar için [LICENSE.md](LICENSE.md) dosyasına bakın.
