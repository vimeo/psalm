<?php
namespace Psalm\Type\Atomic;

class TLowercaseString extends TString
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
    ];

    protected const COERCIBLE_TO = parent::COERCIBLE_TO + [
        TNonEmptyString::class => true,
        TNonFalsyString::class => true,
        TSingleLetter::class => true,
    ];

    public function getKey(bool $include_extra = true): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        return 'lowercase-string';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
