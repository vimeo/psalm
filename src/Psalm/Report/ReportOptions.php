<?php
namespace Psalm\Report;

use Psalm\Report;

class ReportOptions
{
    /**
     * @var bool
     */
    public $use_color = true;

    /**
     * @var bool
     */
    public $show_snippet = true;

    /**
     * @var bool
     */
    public $show_info = true;

    /**
     * @var value-of<Report::SUPPORTED_OUTPUT_TYPES>
     */
    public $format = REPORT::TYPE_CONSOLE;

    /**
     * @var ?string
     */
    public $output_path;

    /**
     * @var bool
     */
    public $show_suggestions = true;
}
