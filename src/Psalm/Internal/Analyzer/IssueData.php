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

    /**
     * @readonly
     */
    public string $link;

    /**
     * @param self::SEVERITY_* $severity
     * @param ?list<DataFlowNodeData|array{label: string, entry_path_type: string}> $taint_trace
     * @param ?list<DataFlowNodeData> $other_references
     */
    public function __construct(
        public string $severity,
        public int $line_from,
        public int $line_to,
        /**
         * @readonly
         */
        public string $type,
        /**
         * @readonly
         */
        public string $message,
        /**
         * @readonly
         */
        public string $file_name,
        /**
         * @readonly
         */
        public string $file_path,
        /**
         * @readonly
         */
        public string $snippet,
        /**
         * @readonly
         */
        public string $selected_text,
        public int $from,
        public int $to,
        public int $snippet_from,
        public int $snippet_to,
        /**
         * @readonly
         */
        public int $column_from,
        /**
         * @readonly
         */
        public int $column_to,
        /**
         * @readonly
         */
        public int $shortcode = 0,
        public int $error_level = -1,
        public ?array $taint_trace = null,
        public ?array $other_references = null,
        /**
         * @readonly
         */
        public ?string $dupe_key = null,
    ) {
        $this->link = $shortcode ? 'https://psalm.dev/' . str_pad((string) $shortcode, 3, "0", STR_PAD_LEFT) : '';
    }
}
