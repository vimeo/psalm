<?php
namespace Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-empty
 */
class TNonEmptyString extends TString
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
    ];

    protected const COERCIBLE_TO = parent::COERCIBLE_TO + [
        TNonFalsyString::class => true,
        TSingleLetter::class => true,
    ];

    public function getId(bool $nested = false): string
    {
        return 'non-empty-string';
    }
}
