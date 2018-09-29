<?php
namespace Psalm\CodeLocation;

use PhpParser;

class ParseErrorLocation extends \Psalm\CodeLocation
{
    /**
     * @param string $file_path
     * @param string $file_name
     * @param string $file_contents
     */
    public function __construct(
        PhpParser\Error $error,
        $file_contents,
        $file_path,
        $file_name
    ) {
        $this->file_start = (int)$error->getAttributes()['startFilePos'];
        $this->file_end = (int)$error->getAttributes()['endFilePos'];
        $this->file_path = $file_path;
        $this->file_name = $file_name;
        $this->single_line = false;

        $this->preview_start = $this->file_start;
        $this->line_number = substr_count(
            substr($file_contents, 0, $this->file_start),
            "\n"
        ) + 1;
    }
}
