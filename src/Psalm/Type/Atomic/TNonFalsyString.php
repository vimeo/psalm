<?php
namespace Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-empty
 */
class TNonFalsyString extends TNonEmptyString
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
    ];

    public function getId(bool $nested = false): string
    {
        return 'non-falsy-string';
    }
}
