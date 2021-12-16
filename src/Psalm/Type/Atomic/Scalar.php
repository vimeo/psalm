<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

abstract class Scalar extends Atomic
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        TScalar::class => true,
    ];

    /** @var array<class-string<Atomic>, true> */
    protected const COERCIBLE_TO = parent::COERCIBLE_TO + [
        TBool::class => true,
        TFloat::class => true,
        TInt::class => true,
        TString::class => true,
    ];

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return true;
    }
}
