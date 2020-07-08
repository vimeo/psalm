<?php

namespace Psalm\Internal\Analyzer;

/**
 * @psalm-immutable
 */
class TaintNodeData
{
    /**
     * @var int
     */
    public $line_from;

    /**
     * @var int
     */
    public $line_to;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $entry_path_type;

    /**
     * @var ?string
     */
    public $entry_path_description;

    /**
     * @var string
     */
    public $file_name;

    /**
     * @var string
     */
    public $file_path;

    /**
     * @var string
     */
    public $snippet;

    /**
     * @var string
     */
    public $selected_text;

    /**
     * @var int
     */
    public $from;

    /**
     * @var int
     */
    public $to;

    /**
     * @var int
     */
    public $snippet_from;

    /**
     * @var int
     */
    public $snippet_to;

    /**
     * @var int
     */
    public $column_from;

    /**
     * @var int
     */
    public $column_to;

    /**
     * @param string $label
     * @param string $entry_path_type
     * @param ?string $entry_path_description
     * @param int $line_from
     * @param int $line_to
     * @param string $file_name
     * @param string $file_path
     * @param string $snippet
     * @param string $selected_text
     * @param int $from
     * @param int $to
     * @param int $snippet_from
     * @param int $snippet_to
     * @param int $column_from
     * @param int $column_to
     */
    public function __construct(
        $label,
        $entry_path_type,
        $entry_path_description,
        $line_from,
        $line_to,
        $file_name,
        $file_path,
        $snippet,
        $selected_text,
        $from,
        $to,
        $snippet_from,
        $snippet_to,
        $column_from,
        $column_to
    ) {
        $this->label = $label;
        $this->entry_path_type = $entry_path_type;
        $this->entry_path_description = $entry_path_description;
        $this->line_from = $line_from;
        $this->line_to = $line_to;
        $this->file_name = $file_name;
        $this->file_path = $file_path;
        $this->snippet = $snippet;
        $this->selected_text = $selected_text;
        $this->from = $from;
        $this->to = $to;
        $this->snippet_from = $snippet_from;
        $this->snippet_to = $snippet_to;
        $this->column_from = $column_from;
        $this->column_to = $column_to;
    }
}
