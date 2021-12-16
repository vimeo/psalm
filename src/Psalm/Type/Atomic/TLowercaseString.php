<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TLowercaseString extends TString
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
    ];

    protected const INTERSECTS = parent::INTERSECTS + [
        TNonEmptyString::class => true,
        TNonFalsyString::class => true,
        TSingleLetter::class => true,
    ];

    public function getId(bool $nested = false): string
    {
        return 'lowercase-string';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
