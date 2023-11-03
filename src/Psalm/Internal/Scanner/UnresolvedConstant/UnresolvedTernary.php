<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class UnresolvedTernary extends UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;

    public UnresolvedConstantComponent $cond;

    public ?UnresolvedConstantComponent $if = null;

    public UnresolvedConstantComponent $else;

    public function __construct(
        UnresolvedConstantComponent $cond,
        ?UnresolvedConstantComponent $if,
        UnresolvedConstantComponent $else
    ) {
        $this->cond = $cond;
        $this->if = $if;
        $this->else = $else;
    }
}
