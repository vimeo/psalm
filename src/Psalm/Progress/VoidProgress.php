<?php

declare(strict_types=1);

namespace Psalm\Progress;

use Override;

final class VoidProgress extends Progress
{
    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function debug(string $message): void
    {
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
    public function alterFileDone(string $file_name): void
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
    public function write(string $message): void
    {
    }
}
