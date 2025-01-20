<?php

declare(strict_types=1);

namespace Psalm\CodeLocation;

use Psalm\CodeLocation;
use Psalm\FileSource;

/** @psalm-immutable */
final class DocblockTypeLocation extends CodeLocation
{
    public function __construct(
        FileSource $file_source,
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

        $this->file_path = $file_source->getFilePath();
        $this->file_name = $file_source->getFileName();
        $this->single_line = false;

        $this->preview_start = $this->file_start;

        $this->docblock_line_number = $line_number;
    }
}
