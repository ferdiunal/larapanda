<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Enums;

/**
 * Supported output formats for `lightpanda fetch --dump`.
 */
enum FetchDumpFormat: string
{
    /** Raw HTML output. */
    case Html = 'html';

    /** Markdown-converted content output. */
    case Markdown = 'markdown';

    /** Structured semantic tree JSON output. */
    case SemanticTree = 'semantic_tree';

    /** Plain-text rendering of the semantic tree. */
    case SemanticTreeText = 'semantic_tree_text';
}
