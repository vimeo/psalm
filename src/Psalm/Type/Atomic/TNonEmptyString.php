<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-empty (every string except '')
 */
class TNonEmptyString extends TString
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
    ];

    protected const INTERSECTS = parent::INTERSECTS + [
        TNonFalsyString::class => true,
        TSingleLetter::class => true,
    ];

    public function getId(bool $nested = false): string
    {
        return 'non-empty-string';
    }
}
