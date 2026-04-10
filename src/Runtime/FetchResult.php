<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime;

use Ferdiunal\Larapanda\Enums\FetchDumpFormat;
use Ferdiunal\Larapanda\Exceptions\UnexpectedFetchOutputFormatException;
use JsonException;

/**
 * Value object combining typed process metadata with fetch output payload.
 */
final readonly class FetchResult
{
    /**
     * @param  ProcessResult  $process  Completed process metadata.
     * @param  string  $output  Parsed fetch output payload.
     * @param  FetchDumpFormat|null  $dumpFormat  Effective dump format used during fetch execution.
     */
    public function __construct(
        private ProcessResult $process,
        private string $output,
        private ?FetchDumpFormat $dumpFormat = null,
    ) {}

    /**
     * Return the underlying process result.
     */
    public function process(): ProcessResult
    {
        return $this->process;
    }

    /**
     * Return fetch output payload.
     */
    public function output(): string
    {
        return $this->output;
    }

    /**
     * Return the fetch dump format used to produce this output.
     */
    public function dumpFormat(): ?FetchDumpFormat
    {
        return $this->dumpFormat;
    }

    /**
     * Return output as HTML.
     *
     * @throws UnexpectedFetchOutputFormatException When output format is not `html`.
     */
    public function asHtml(): string
    {
        $this->assertDumpFormat(FetchDumpFormat::Html);

        return $this->output;
    }

    /**
     * Return output as Markdown.
     *
     * @throws UnexpectedFetchOutputFormatException When output format is not `markdown`.
     */
    public function asMarkdown(): string
    {
        $this->assertDumpFormat(FetchDumpFormat::Markdown);

        return $this->output;
    }

    /**
     * Return output as semantic tree JSON decoded into an associative array.
     *
     * @return array<string, mixed>
     *
     * @throws UnexpectedFetchOutputFormatException When output format is not `semantic_tree` or payload is invalid JSON.
     */
    public function asSemanticTree(): array
    {
        $this->assertDumpFormat(FetchDumpFormat::SemanticTree);

        try {
            $decoded = json_decode($this->output, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw UnexpectedFetchOutputFormatException::invalidSemanticTree($exception->getMessage(), $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw UnexpectedFetchOutputFormatException::invalidSemanticTree('Expected a JSON object at the root.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Return output as semantic tree plain-text representation.
     *
     * @throws UnexpectedFetchOutputFormatException When output format is not `semantic_tree_text`.
     */
    public function asSemanticTreeText(): string
    {
        $this->assertDumpFormat(FetchDumpFormat::SemanticTreeText);

        return $this->output;
    }

    /**
     * Assert that the response was fetched with the expected dump format.
     *
     * @throws UnexpectedFetchOutputFormatException When expected and actual formats do not match.
     */
    private function assertDumpFormat(FetchDumpFormat $expected): void
    {
        if ($this->dumpFormat !== $expected) {
            throw UnexpectedFetchOutputFormatException::forExpected($expected, $this->dumpFormat);
        }
    }
}
