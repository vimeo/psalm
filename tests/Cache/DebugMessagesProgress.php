<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress;
use RuntimeException;

use function array_filter;
use function count;
use function str_contains;

/**
 * Keeps the debug messages, and ends the run at the one that starts a process the test doesn't want to see started.
 */
final class DebugMessagesProgress extends Progress
{
    /** @var list<string> */
    private array $messages = [];

    public function __construct(private readonly ?string $abort_at = null)
    {
    }

    /**
     * @psalm-capabilities read-props
     */
    public function countMessagesContaining(string $text): int
    {
        return count(array_filter($this->messages, static fn(string $message): bool => str_contains($message, $text)));
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    public function debug(string $message): void
    {
        $this->messages[] = $message;

        if ($this->abort_at !== null && str_contains($message, $this->abort_at)) {
            throw new RuntimeException($message);
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
}
