<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class EncapsulationTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var bool
     */
    public $terminated = false;
}
