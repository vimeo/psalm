<?php

namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\Range;

/**
 * @internal
 */
final class Reference
{
    public string $file_path;
    public string $symbol;
    public Range $range;

    public function __construct(string $file_path, string $symbol, Range $range)
    {
        $this->file_path = $file_path;
        $this->symbol = $symbol;
        $this->range = $range;
    }
}
