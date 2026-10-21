<?php

declare(strict_types=1);

namespace Psalm\Tests\Progress;

use Override;
use Psalm\Progress\DefaultProgress;

final class EchoProgress extends DefaultProgress
{
    #[Override]
    public function write(string $message): void
    {
        echo self::terminalText($message);
    }

    /**
     * Messages Psalm writes to the terminal (e.g. --debug-emitted-issues, with the text of docblocks) end up here:
     * the tests read them with output buffering, they're never served as HTML
     *
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     * @psalm-pure
     */
    private static function terminalText(string $message): string
    {
        return $message;
    }
}
