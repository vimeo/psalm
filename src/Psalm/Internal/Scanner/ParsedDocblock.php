<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner;

use function explode;
use function trim;

/**
 * @internal
 */
final class ParsedDocblock
{
    /** @var array<string, array<int, string>> */
    public array $combined_tags = [];

    private static bool $shouldAddNewLineBetweenAnnotations = true;

    /** @param array<string, array<int, string>> $tags */
    public function __construct(public string $description, public array $tags, public string $first_line_padding = '')
    {
    }

    public function render(string $left_padding): string
    {
        $doc_comment_text = '/**' . "\n";

        $trimmed_description = trim($this->description);

        if ($trimmed_description !== '') {
            $description_lines = explode("\n", $this->description);

            foreach ($description_lines as $line) {
                $doc_comment_text .= $left_padding . ' *' . (trim($line) ? ' ' . $line : '') . "\n";
            }
        }

        if ($this->tags) {
            if ($trimmed_description !== '') {
                $doc_comment_text .= $left_padding . ' *' . "\n";
            }

            $last_type = null;

            foreach ($this->tags as $type => $lines) {
                if ($last_type !== null
                    && $last_type !== 'psalm-return'
                    && static::shouldAddNewLineBetweenAnnotations()
                ) {
                    $doc_comment_text .= $left_padding . ' *' . "\n";
                }

                foreach ($lines as $line) {
                    $doc_comment_text .= $left_padding . ' * @' . $type . ($line !== '' ? ' ' . $line : '') . "\n";
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
     */
    public static function addNewLineBetweenAnnotations(bool $should = true): void
    {
        static::$shouldAddNewLineBetweenAnnotations = $should;
    }

    public static function resetNewlineBetweenAnnotations(): void
    {
        static::$shouldAddNewLineBetweenAnnotations = true;
    }
}
