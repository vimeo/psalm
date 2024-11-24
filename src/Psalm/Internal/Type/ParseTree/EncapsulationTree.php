<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class EncapsulationTree extends ParseTree
{
    public bool $terminated = false;
}
