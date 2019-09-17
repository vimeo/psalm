<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

class UnresolvedTernary extends UnresolvedConstantComponent
{
    public $cond;
    public $if;
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
