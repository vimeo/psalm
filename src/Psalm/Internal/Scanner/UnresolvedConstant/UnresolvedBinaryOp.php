<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 *
 * @internal
 */
abstract class UnresolvedBinaryOp extends UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;

    /** @var UnresolvedConstantComponent */
    public $left;

    /** @var UnresolvedConstantComponent */
    public $right;

    public function __construct(UnresolvedConstantComponent $left, UnresolvedConstantComponent $right)
    {
        $this->left = $left;
        $this->right = $right;
    }
}
