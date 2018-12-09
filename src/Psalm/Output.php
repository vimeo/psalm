<?php
namespace Psalm;

abstract class Output
{
    /**
     * @var array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     * file_name: string, file_path: string, snippet: string, from: int, to: int,
     * snippet_from: int, snippet_to: int, column_from: int, column_to: int, selected_text: string}>
     */
    protected $issues_data;

    /** @var bool */
    protected $use_color;

    /** @var bool */
    protected $show_snippet;

    /** @var bool */
    protected $show_info;

    /**
     * @param array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int,
     *  snippet_from: int, snippet_to: int, column_from: int, column_to: int, selected_text: string}> $issues_data
     * @param bool $use_color
     * @param bool $show_snippet
     * @param bool $show_info
     */
    public function __construct(array $issues_data, bool $use_color, bool $show_snippet = true, bool $show_info = true)
    {
        $this->issues_data = $issues_data;
        $this->use_color = $use_color;
        $this->show_snippet = $show_snippet;
        $this->show_info = $show_info;
    }

    /**
     * @return string
     */
    abstract public function create(): string;
}
