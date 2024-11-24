<?php

declare(strict_types=1);

namespace Psalm\Report;

use Psalm\Report;

final class ReportOptions
{
    public bool $use_color = true;

    public bool $show_snippet = true;

    public bool $show_info = true;

    /**
     * @var Report::TYPE_*
     */
    public string $format = Report::TYPE_CONSOLE;

    public bool $pretty = false;

    public ?string $output_path = null;

    public bool $show_suggestions = true;

    public bool $in_ci = false;
}
