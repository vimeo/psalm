<?php

declare(strict_types=1);

namespace Psalm\Tests\Progress;

use Override;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress;

/** @psalm-mutable */
final class RecordingProgress extends Progress
{
    /** @var list<string> */
    public array $debug_messages = [];

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    public function debug(string $message): void
    {
        $this->debug_messages[] = $message;
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function startPhase(Phase $phase, int $threads = 1): void
    {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function expand(int $number_of_tasks): void
    {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function taskDone(int $level): void
    {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function finish(): void
    {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function alterFileDone(string $file_name): void
    {
    }
}
