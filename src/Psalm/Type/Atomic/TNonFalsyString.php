<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-falsy (every string except '' and '0')
 */
class TNonFalsyString extends TNonEmptyString
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
    ];

    public function getId(bool $nested = false): string
    {
        return 'non-falsy-string';
    }
}
