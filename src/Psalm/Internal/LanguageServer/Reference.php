<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\Range;

/**
 * @internal
 */
final class Reference
{
    public function __construct(public string $file_path, public string $symbol, public Range $range)
    {
    }
}
