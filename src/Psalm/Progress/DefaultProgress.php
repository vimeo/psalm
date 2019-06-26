<?php
namespace Psalm\Progress;

use function str_repeat;
use function strlen;

class DefaultProgress extends LongProgress
{
    const TOO_MANY_FILES = 1500;

    public function taskDone(int $level): void
    {
        if ($this->number_of_tasks > self::TOO_MANY_FILES) {
            ++$this->progress;

            $inner_progress = self::renderInnerProgressBar(
                self::NUMBER_OF_COLUMNS,
                $this->progress / $this->number_of_tasks
            );

            $this->write($inner_progress . ' ' . $this->getOverview() . "\r");
        } else {
            parent::taskDone($level);
        }
    }

    /**
     * Fully stolen from
     * https://github.com/phan/phan/blob/d61a624b1384ea220f39927d53fd656a65a75fac/src/Phan/CLI.php
     * Renders a unicode progress bar that goes from light (left) to dark (right)
     * The length in the console is the positive integer $length
     * @see https://en.wikipedia.org/wiki/Block_Elements
     */
    private static function renderInnerProgressBar(int $length, float $p) : string
    {
        $current_float = $p * $length;
        $current = (int)$current_float;
        $rest = \max($length - $current, 0);

        if (!self::doesTerminalSupportUtf8()) {
            // Show a progress bar of "XXXX>------" in Windows when utf-8 is unsupported.
            $progress_bar = str_repeat("X", $current);
            $delta = $current_float - $current;
            if ($delta > 0.5) {
                $progress_bar .= ">" . str_repeat("-", $rest - 1);
            } else {
                $progress_bar .= str_repeat("-", $rest);
            }
            return $progress_bar;
        }

        // The left-most characters are "Light shade"
        $progress_bar = str_repeat("\u{2588}", $current);
        $delta = $current_float - $current;
        if ($delta > 3.0 / 4) {
            $progress_bar .= "\u{258A}" . str_repeat("\u{2591}", $rest - 1);
        } elseif ($delta > 2.0 / 4) {
            $progress_bar .= "\u{258C}" . str_repeat("\u{2591}", $rest - 1);
        } elseif ($delta > 1.0 / 4) {
            $progress_bar .= "\u{258E}" . str_repeat("\u{2591}", $rest - 1);
        } else {
            $progress_bar .= str_repeat("\u{2591}", $rest);
        }

        return $progress_bar;
    }

    public function finish(): void
    {
        if ($this->number_of_tasks > self::TOO_MANY_FILES) {
            $this->write(str_repeat(' ', self::NUMBER_OF_COLUMNS + strlen($this->getOverview()) + 1) . "\r");
        } else {
            parent::finish();
        }
    }
}
