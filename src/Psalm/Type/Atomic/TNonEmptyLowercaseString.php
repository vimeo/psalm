<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes a non-empty-string where every character is lowercased. (which can also result from a `strtolower` call).
 */
class TNonEmptyLowercaseString extends TNonEmptyString
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
        TLowercaseString::class => true,
    ];

    protected const INTERSECTS = parent::INTERSECTS + [
        TNonFalsyString::class => true,
        TSingleLetter::class => true,
    ];

    public function getId(bool $nested = false): string
    {
        return 'non-empty-lowercase-string';
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
