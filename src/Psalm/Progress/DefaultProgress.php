<?php
namespace Psalm\Progress;

class DefaultProgress extends LongProgress
{
    const TOO_MANY_FILES = 1500;

    public function taskDone(bool $successful): void
    {
        if ($this->number_of_tasks > self::TOO_MANY_FILES) {
            ++$this->progress;

            $expected_bars = round(($this->progress / $this->number_of_tasks) * self::NUMBER_OF_COLUMNS);

            $inner_progress = str_repeat(self::doesTerminalSupportUtf8() ? '░' : 'X', $expected_bars);

            if ($expected_bars !== self::NUMBER_OF_COLUMNS) {
                $expected_bars--;
            }

            $progress_bar = $inner_progress . str_repeat(' ', self::NUMBER_OF_COLUMNS - $expected_bars);

            $this->write($progress_bar . ' ' . $this->getOverview() . "\r");
        } else {
            parent::taskDone($successful);
        }
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
