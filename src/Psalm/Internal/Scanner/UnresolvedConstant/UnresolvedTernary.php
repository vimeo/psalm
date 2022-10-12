<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 *
 * @internal
 */
class UnresolvedTernary extends UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;

    /** @var UnresolvedConstantComponent */
    public $cond;
    /** @var UnresolvedConstantComponent|null */
    public $if;
    /** @var UnresolvedConstantComponent */
    public $else;

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
