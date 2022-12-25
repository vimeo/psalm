<?php

namespace Psalm\Type\Atomic;

/**
 * @deprecated Use {@see TString} with {@see TString::$lowercase} set to true.
 * @psalm-immutable
 */
final class TLowercaseString extends TString
{
    public function __construct(bool $from_docblock = false)
    {
        parent::__construct($from_docblock, true);
    }
}
