<?php

declare(strict_types=1);

namespace Psalm\CodeLocation;

use Psalm\CodeLocation;

/** @psalm-immutable */
final class ComposerJsonLocation extends CodeLocation
{
    public function __construct(
        string $file_path,
        int $file_start,
        int $file_end,
        int $line_number,
    ) {
        $this->file_start = $file_start;
        // matches how CodeLocation works
        $this->file_end = $file_end - 1;

        $this->raw_file_start = $file_start;
        $this->raw_file_end = $file_end;
        $this->raw_line_number = $line_number;

        $this->file_path = $file_path;
        $this->file_name = 'composer.json';
        $this->single_line = false;

        $this->preview_start = $this->file_start;

        $this->docblock_line_number = $line_number;
    }
}
