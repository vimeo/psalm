<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes a string that has a length of 1
 */
class TSingleLetter extends TString
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
    ];
}
