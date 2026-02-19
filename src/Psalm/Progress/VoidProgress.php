<?php

declare(strict_types=1);

namespace Psalm\Progress;

use Override;

final class VoidProgress extends Progress
{
    #[Override]
    public function debug(string $message): void
    {
    }

    #[Override]
    public function startPhase(Phase $phase, int $threads = 1): void
    {
    }

    #[Override]
    public function alterFileDone(string $file_name): void
    {
    }
    
    #[Override]
    public function expand(int $number_of_tasks): void
    {
    }

    #[Override]
    public function taskDone(int $level): void
    {
    }

    #[Override]
    public function finish(): void
    {
    }

    #[Override]
    public function write(string $message): void
    {
    }
}
