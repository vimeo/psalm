<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use Override;
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
    public function write(string $message): void
    {
        if ($this->server) {
            $this->server->logInfo(str_replace("\n", "", $message));
        }
    }
}
