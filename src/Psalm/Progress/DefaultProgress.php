<?php
namespace Psalm\Progress;

class DefaultProgress extends Progress
{
    public const NUMBER_OF_COLUMNS = 60;

    /** @var int|null */
    private $number_of_tasks;

    /** @var int */
    private $progress = 0;

    public function startScanningFiles(): void
    {
        $this->write('Scanning files...' . "\n");
    }

    public function startAnalyzingFiles(): void
    {
        $this->write('Analyzing files...' . "\n");
    }

    public function start(int $number_of_tasks): void
    {
        $this->number_of_tasks = $number_of_tasks;
        $this->progress = 0;
    }

    public function taskDone(bool $successful): void
    {
        if ($successful) {
            $this->write('.');
        } else {
            $this->write('F');
        }

        ++$this->progress;

        if (($this->progress % self::NUMBER_OF_COLUMNS) !== 0) {
            return;
        }

        $this->printOverview();
        $this->write(PHP_EOL);
    }

    public function finish(): void
    {
        $this->write(PHP_EOL);
    }

    private function printOverview(): void
    {
        if ($this->number_of_tasks === null) {
            throw new \LogicException('Progress::start() should be called before Progress::startDone()');
        }

        $leadingSpaces = 1 + strlen((string) $this->number_of_tasks) - strlen((string) $this->progress);
        $percentage = round($this->progress / $this->number_of_tasks * 100);
        $message = sprintf(
            '%s%s / %s (%s%%)',
            str_repeat(' ', $leadingSpaces),
            $this->progress,
            $this->number_of_tasks,
            $percentage
        );

        $this->write($message);
    }
}
