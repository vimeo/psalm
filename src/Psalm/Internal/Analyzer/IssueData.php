<?php

namespace Psalm\Internal\Analyzer;

class IssueData
{
    /**
     * @var string
     */
    public $severity;

    /**
     * @var int
     * @readonly
     */
    public $line_from;

    /**
     * @var int
     * @readonly
     */
    public $line_to;

    /**
     * @var string
     * @readonly
     */
    public $type;

    /**
     * @var string
     * @readonly
     */
    public $message;

    /**
     * @var string
     * @readonly
     */
    public $file_name;

    /**
     * @var string
     * @readonly
     */
    public $file_path;

    /**
     * @var string
     * @readonly
     */
    public $snippet;

    /**
     * @var string
     * @readonly
     */
    public $selected_text;

    /**
     * @var int
     * @readonly
     */
    public $from;

    /**
     * @var int
     * @readonly
     */
    public $to;

    /**
     * @var int
     * @readonly
     */
    public $snippet_from;

    /**
     * @var int
     * @readonly
     */
    public $snippet_to;

    /**
     * @var int
     * @readonly
     */
    public $column_from;

    /**
     * @var int
     * @readonly
     */
    public $column_to;

    /**
     * @var int
     */
    public $error_level;

    /**
     * @var int
     * @readonly
     */
    public $shortcode;

    /**
     * @var string
     * @readonly
     */
    public $link;

    /**
     * @param string $severity
     * @param int $line_from
     * @param int $line_to
     * @param string $type
     * @param string $message
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
     * @param int $error_level
     * @param int $shortcode
     */
    public function __construct(
        $severity,
        $line_from,
        $line_to,
        $type,
        $message,
        $file_name,
        $file_path,
        $snippet,
        $selected_text,
        $from,
        $to,
        $snippet_from,
        $snippet_to,
        $column_from,
        $column_to,
        $shortcode = 0,
        $error_level = -1
    ) {
        $this->severity = $severity;
        $this->line_from = $line_from;
        $this->line_to = $line_to;
        $this->type = $type;
        $this->message = $message;
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
        $this->shortcode = $shortcode;
        $this->error_level = $error_level;
        $this->link = 'https://psalm.dev/' . \str_pad((string) $shortcode, 3, "0", \STR_PAD_LEFT);
    }
}
