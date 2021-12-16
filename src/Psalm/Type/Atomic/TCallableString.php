<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `callable-string` type, used to represent an unknown string that is also `callable`.
 */
class TCallableString extends TNonEmptyString
{
    // TODO this needs checked over, it was done back when TCallableString extended TString instead of TNonEmptyString
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
        TNonEmptyString::class => true,
        TNonFalsyString::class => true,
    ];

    // TODO this needs checked over, it was done back when TCallableString extended TString instead of TNonEmptyString
    protected const INTERSECTS = parent::INTERSECTS + [
        TLowercaseString::class => true,
        TNonEmptyLowercaseString::class => true,
        TSingleLetter::class => true,
    ];

    public function getKey(bool $include_extra = true): string
    {
        return 'callable-string';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'string';
    }
}
