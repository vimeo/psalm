<?php

namespace Psalm\Internal\Scanner;

use function trim;
use function explode;

class ParsedDocblock
{
    /** @var string */
    public $description;

    /** @var array<string, array<int, string>> */
    public $tags = [];

    /** @var array<string, array<int, string>> */
    public $combined_tags = [];

    /**
     * @var bool
     */
    private static $shouldAddNewLineBetweenAnnotations = true;

    /** @param array<string, array<int, string>> $tags */
    public function __construct(string $description, array $tags)
    {
        $this->description = $description;
        $this->tags = $tags;
    }

    public function render(string $left_padding) : string
    {
        $doc_comment_text = '/**' . "\n";

        $trimmed_description = trim($this->description);

        if (!empty($trimmed_description)) {
            $description_lines = explode("\n", $this->description);

            foreach ($description_lines as $line) {
                $doc_comment_text .= $left_padding . (trim($line) ? ' ' . $line : '') . "\n";
            }
        }

        if ($this->tags) {
            $last_type = null;

            foreach ($this->tags as $type => $lines) {
                if ($last_type !== null
                    && $last_type !== 'psalm-return'
                    && static::shouldAddNewLineBetweenAnnotations()
                ) {
                    $doc_comment_text .= $left_padding . ' *' . "\n";
                }

                foreach ($lines as $line) {
                    $doc_comment_text .= $left_padding . ' * @' . $type . ' ' . $line . "\n";
                }

                $last_type = $type;
            }
        }

        $doc_comment_text .= $left_padding . ' */' . "\n" . $left_padding;

        return $doc_comment_text;
    }

    private static function shouldAddNewLineBetweenAnnotations(): bool
    {
        return static::$shouldAddNewLineBetweenAnnotations;
    }

    /**
     * Sets whether a new line should be added between the annotations or not.
     *
     * @param bool $should
     */
    public static function addNewLineBetweenAnnotations(bool $should = true): void
    {
        static::$shouldAddNewLineBetweenAnnotations = $should;
    }
}
