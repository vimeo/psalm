<?php

declare(strict_types=1);

namespace Psalm\Internal\FileManipulation;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;

use function array_pop;
use function count;
use function explode;
use function get_cfg_var;
use function ini_get;
use function is_string;
use function max;
use function min;
use function ord;
use function str_ends_with;
use function strlen;
use function strrev;
use function strspn;
use function strtr;
use function substr;
use function substr_count;

/**
 * Renders the changes Psalter would make to a file: a numbered title line followed by a git-style unified diff.
 * Plain output stays a valid patch for `git apply`; colored output drops the file headers and highlights changes.
 *
 * @internal
 */
final class UnifiedDiffRenderer
{
    private const RESET = "\e[0m";

    public function __construct(
        private readonly bool $use_color = false,
        private readonly bool $in_ci = false,
    ) {
    }

    /**
     * @param string $relative_path forward-slash path relative to the project root
     */
    public function render(
        int $index,
        string $relative_path,
        string $absolute_path,
        string $old_contents,
        string $new_contents,
    ): string {
        $diff = (new Differ(
            new StrictUnifiedDiffOutputBuilder([
                'fromFile' => 'a/' . $relative_path,
                'toFile' => 'b/' . $relative_path,
            ]),
        ))->diff($old_contents, $new_contents);

        $first_changed_line = self::getFirstChangedLine($old_contents, $new_contents);
        $title = $index . ') ' . $relative_path . ':' . $first_changed_line;
        $separator = $index > 1 ? "\n" : '';

        if (!$this->use_color) {
            return $separator . $title . "\n" . $diff;
        }

        $lines = explode("\n", $diff);
        // every diff line, including the last one, ends with a newline
        array_pop($lines);

        return $separator
            . $this->formatTitle($title, $absolute_path, $first_changed_line)
            . "\n"
            . self::colorize($lines);
    }

    /**
     * @psalm-pure
     */
    private static function getFirstChangedLine(string $old_contents, string $new_contents): int
    {
        $line = substr_count($new_contents, "\n", 0, strspn($old_contents ^ $new_contents, "\0")) + 1;
        $last_line = substr_count($new_contents, "\n") + (str_ends_with($new_contents, "\n") ? 0 : 1);

        return min($line, max(1, $last_line));
    }

    private function formatTitle(string $title, string $absolute_path, int $line): string
    {
        $title = "\e[1m" . $title . self::RESET;

        if ($this->in_ci) {
            return $title;
        }

        $link_format = ini_get('xdebug.file_link_format');
        if (!is_string($link_format) || $link_format === '') {
            $link_format = get_cfg_var('xdebug.file_link_format');
        }

        if (!is_string($link_format) || $link_format === '') {
            $link_format = 'file://%f#L%l';
        }

        $link = strtr($link_format, ['%f' => $absolute_path, '%l' => (string) $line]);

        return "\e]8;;" . $link . "\e\\" . $title . "\e]8;;\e\\";
    }

    /**
     * @param list<string> $lines
     * @psalm-pure
     */
    private static function colorize(array $lines): string
    {
        $output = '';
        $count = count($lines);
        $i = 2;

        while ($i < $count) {
            $first_char = $lines[$i][0];

            if ($first_char === '-') {
                $removed_end = $i + 1;
                while ($removed_end < $count && $lines[$removed_end][0] === '-') {
                    ++$removed_end;
                }

                $added_end = $removed_end;
                while ($added_end < $count && $lines[$added_end][0] === '+') {
                    ++$added_end;
                }

                $run_length = $removed_end - $i;

                if ($added_end - $removed_end !== $run_length) {
                    for (; $i < $removed_end; $i++) {
                        $output .= "\e[31m" . $lines[$i] . self::RESET . "\n";
                    }

                    continue;
                }

                $removed_output = '';
                $added_output = '';
                for ($j = 0; $j < $run_length; $j++) {
                    [$removed, $added] = self::highlightChange(
                        substr($lines[$i + $j], 1),
                        substr($lines[$removed_end + $j], 1),
                    );
                    $removed_output .= "\e[31m-" . $removed . self::RESET . "\n";
                    $added_output .= "\e[32m+" . $added . self::RESET . "\n";
                }

                $output .= $removed_output . $added_output;

                $i = $added_end;
                continue;
            }

            $output .= match ($first_char) {
                '+' => "\e[32m" . $lines[$i] . self::RESET,
                '@' => "\e[36m" . $lines[$i] . self::RESET,
                '\\' => "\e[2m" . $lines[$i] . self::RESET,
                default => $lines[$i],
            } . "\n";
            ++$i;
        }

        return $output;
    }

    /**
     * Marks the differing middle of two lines in reverse video, never splitting a multibyte character.
     *
     * @return array{string, string}
     * @psalm-pure
     */
    private static function highlightChange(string $old, string $new): array
    {
        $old_length = strlen($old);
        $new_length = strlen($new);

        $prefix = strspn($old ^ $new, "\0");
        while ($prefix > 0 && (self::isContinuationByte($old, $prefix) || self::isContinuationByte($new, $prefix))) {
            --$prefix;
        }

        $suffix = min(strspn(strrev($old) ^ strrev($new), "\0"), min($old_length, $new_length) - $prefix);
        while ($suffix > 0
            && (self::isContinuationByte($old, $old_length - $suffix)
                || self::isContinuationByte($new, $new_length - $suffix))
        ) {
            --$suffix;
        }

        if ($prefix + $suffix === 0) {
            return [$old, $new];
        }

        return [
            self::markMiddle($old, $prefix, $suffix),
            self::markMiddle($new, $prefix, $suffix),
        ];
    }

    /**
     * @psalm-pure
     */
    private static function markMiddle(string $line, int $prefix, int $suffix): string
    {
        $middle = substr($line, $prefix, strlen($line) - $prefix - $suffix);

        return substr($line, 0, $prefix)
            . ($middle === '' ? '' : "\e[7m" . $middle . "\e[27m")
            . substr($line, strlen($line) - $suffix);
    }

    /**
     * @psalm-pure
     */
    private static function isContinuationByte(string $text, int $position): bool
    {
        return $position < strlen($text) && (ord($text[$position]) & 0xC0) === 0x80;
    }
}
