<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use function str_pad;

use const STR_PAD_LEFT;

/**
 * @internal
 */
final class IssueData
{
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_ERROR = 'error';

    public readonly string $link;

    /**
     * @param self::SEVERITY_* $severity
     * @param ?list<DataFlowNodeData|array{label: string, entry_path_type: string}> $taint_trace
     * @param ?list<DataFlowNodeData> $other_references
     */
    public function __construct(
        public string $severity,
        public int $line_from,
        public int $line_to,
        public readonly string $type,
        public readonly string $message,
        public readonly string $file_name,
        public readonly string $file_path,
        public readonly string $snippet,
        public readonly string $selected_text,
        public int $from,
        public int $to,
        public int $snippet_from,
        public int $snippet_to,
        public readonly int $column_from,
        public readonly int $column_to,
        public readonly int $shortcode = 0,
        public int $error_level = -1,
        public ?array $taint_trace = null,
        public ?array $other_references = null,
        public readonly ?string $dupe_key = null,
    ) {
        $this->link = $shortcode ? 'https://psalm.dev/' . str_pad((string) $shortcode, 3, "0", STR_PAD_LEFT) : '';
    }
}
