<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress;

use function preg_match;

/**
 * Remembers the files that were scanned and prints nothing.
 */
final class RecordingProgress extends Progress
{
    /** @var list<string> */
    public array $scanned_files = [];

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    public function debug(string $message): void
    {
        if (preg_match('/^(?:Deep s|S)canning (.+)\n$/', $message, $matches) === 1) {
            $this->scanned_files[] = $matches[1];
        }
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function startPhase(Phase $phase, int $threads = 1): void
    {
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function expand(int $number_of_tasks): void
    {
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function taskDone(int $level): void
    {
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function finish(): void
    {
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function alterFileDone(string $file_name): void
    {
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function write(string $message): void
    {
    }
}
