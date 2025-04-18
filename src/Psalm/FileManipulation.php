<?php

declare(strict_types=1);

namespace Psalm;

use function sha1;
use function strlen;
use function strrpos;
use function substr;
use function trim;

final class FileManipulation
{
    public function __construct(
        public int $start,
        public int $end,
        public string $insertion_text,
        public bool $preserve_indentation = false,
        public bool $remove_trailing_newline = false,
    ) {
    }

    public function getKey(): string
    {
        return $this->start === $this->end
            ? ($this->start . ':' . sha1($this->insertion_text))
            : ($this->start . ':' . $this->end);
    }

    public function transform(string $existing_contents): string
    {
        if ($this->preserve_indentation) {
            $newline_pos = strrpos($existing_contents, "\n", $this->start - strlen($existing_contents));

            $newline_pos = $newline_pos !== false ? $newline_pos + 1 : 0;

            $indentation = substr($existing_contents, $newline_pos, $this->start - $newline_pos);

            if (trim($indentation) === '') {
                $this->insertion_text .= $indentation;
            }
        }

        if ($this->remove_trailing_newline
            && strlen($existing_contents) > $this->end
            && $existing_contents[$this->end] === "\n"
        ) {
            $newline_pos = strrpos($existing_contents, "\n", $this->start - strlen($existing_contents));

            $newline_pos = $newline_pos !== false ? $newline_pos + 1 : 0;

            $indentation = substr($existing_contents, $newline_pos, $this->start - $newline_pos);

            if (trim($indentation) === '') {
                $this->start -= strlen($indentation);
                $this->end++;
            }
        }

        return substr($existing_contents, 0, $this->start)
            . $this->insertion_text
            . substr($existing_contents, $this->end);
    }
}
