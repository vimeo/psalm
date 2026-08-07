<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use Psalm\Progress\Progress;

use function str_contains;

final class RecordingProgress extends Progress
{
    /** @var list<string> */
    private array $debug_messages = [];

    #[Override]
    public function debug(string $message): void
    {
        $this->debug_messages[] = $message;
    }

    public function countDebugMessagesContaining(string $needle): int
    {
        $count = 0;

        foreach ($this->debug_messages as $message) {
            if (str_contains($message, $needle)) {
                $count++;
            }
        }

        return $count;
    }

    #[Override]
    public function write(string $message): void
    {
    }
}
