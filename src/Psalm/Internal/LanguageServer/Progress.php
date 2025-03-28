<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use Override;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress as Base;

use function str_replace;

/**
 * @internal
 */
final class Progress extends Base
{

    private ?LanguageServer $server = null;

    public function setServer(LanguageServer $server): void
    {
        $this->server = $server;
    }

    #[Override]
    public function debug(string $message): void
    {
        if ($this->server) {
            $this->server->logDebug(str_replace("\n", "", $message));
        }
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
        if ($this->server) {
            $this->server->logInfo(str_replace("\n", "", $message));
        }
    }
}
