<?php

namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\Range;

/**
 * @internal
 */
class Reference
{
    /**
     * @var string
     */
    public $file_path;

    /**
     * @var string
     */
    public $symbol;


    /**
     * @var Range
     */
    public $range;

    public function __construct(string $file_path, string $symbol, Range $range)
    {
        $this->file_path = $file_path;
        $this->symbol = $symbol;
        $this->range = $range;
    }
}