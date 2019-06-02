<?php
namespace Psalm\Progress;

class LongProgress extends Progress
{
    public const NUMBER_OF_COLUMNS = 60;

    /** @var int|null */
    protected $number_of_tasks;

    /** @var int */
    protected $progress = 0;

    /** @var bool */
    protected $print_failures = false;

    public function __construct(bool $print_failures = true)
    {
        $this->print_failures = $print_failures;
    }

    public function startScanningFiles(): void
    {
        $this->write('Scanning files...' . "\n");
    }

    public function startAnalyzingFiles(): void
    {
        $this->write('Analyzing files...' . "\n\n");
    }

    public function startAlteringFiles(): void
    {
        $this->write('Altering files...' . "\n");
    }

    public function alterFileDone(string $file_path) : void
    {
        $this->write('Altered ' . $file_path . "\n");
    }

    public function start(int $number_of_tasks): void
    {
        $this->number_of_tasks = $number_of_tasks;
        $this->progress = 0;
    }

    public function taskDone(bool $successful): void
    {
        if ($successful || !$this->print_failures) {
            $this->write(self::doesTerminalSupportUtf8() ? '░' : '_');
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

    protected function getOverview() : string
    {
        if ($this->number_of_tasks === null) {
            throw new \LogicException('Progress::start() should be called before Progress::startDone()');
        }

        $leadingSpaces = 1 + strlen((string) $this->number_of_tasks) - strlen((string) $this->progress);
        $percentage = round($this->progress / $this->number_of_tasks * 100);
        return sprintf(
            '%s%s / %s (%s%%)',
            str_repeat(' ', $leadingSpaces),
            $this->progress,
            $this->number_of_tasks,
            $percentage
        );
    }

    private function printOverview(): void
    {
        $this->write($this->getOverview());
    }
}
